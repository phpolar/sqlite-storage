<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use Countable;
use Phpolar\Storage\{
    AbstractStorage,
    Closable,
    Loadable,
    Persistable,
};
use SQLite3;
use SQLite3Exception;

/**
 * Adds support for managing data in a SQLite database.
 *
 * Data is automatically loaded
 */
final class SqliteStorage extends AbstractStorage implements
    Closable,
    Countable,
    Loadable,
    Persistable
{
    public function __construct(
        /**
         * The SQLite3 connection to use.
         */
        private readonly SQLite3 $connection,
        /**
         * The name of the table to use.
         */
        private readonly string $tableName,
        /**
         * The class name of the type being stored.
         */
        private readonly string $typeClassName,
    ) {
        if (class_exists($typeClassName) === false) {
            throw new NonExistentClassException(
                typeClassName: $typeClassName,
            );
        }

        if (
            method_exists($typeClassName, "getPrimaryKey") === false
            && property_exists($typeClassName, "id") === false
        ) {
            throw new NonExistentPrimaryKeyAccessorException(
                typeClassName: $typeClassName,
            );
        }

        parent::__construct(
            new StorageLifeCycleHooks($this),
        );
    }

    /**
     * Close the connection to the database.
     */
    public function close(): void
    {
        $this->connection->close();
    }

    /**
     * Load all data for the given type into memory.
     *
     * The class must have either
     * If the items have a `getPrimaryKey` method,
     * its return value will be used as the key
     * in the in-memory collection. Otherwise,
     */
    public function load(): void
    {
        $result = $this->connection->query(
            <<<SQL
            SELECT * FROM "{$this->tableName}";
            SQL
        );

        if ($result === false) {
            $this->clear();

            throw new SQLite3Exception(
                message: $this->connection->lastErrorMsg(),
                code: $this->connection->lastErrorCode(),
            );
        }

        while (($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
            $item = new $this->typeClassName($row);
            $key = (string) (method_exists($item, "getPrimaryKey") === true
                ? $item->getPrimaryKey()
                : (property_exists($item, "id") === true
                    ? $item->id
                    : "" /* The class is already checked for the getPrimaryKey method or id property */));
            $this->save($key, $item);
        }
    }

    /**
     * Persist changes to the database.
     */
    public function persist(): void
    {
        if (count($this) === 0) {
            return;
        }

        /**
         * @var object[]
         */
        $items = $this->findAll();

        $this->validateItemTypeOrThrow($items[0]);
        $this->validateColumnNamesOrThrow($items[0]);

        $this->upsertItems($items);
        $this->deleteRemovedItems($items);
    }


    /**
     * Persist added or updated items in the
     * in-memory collection to the database.
     *
     * @param object[] $items
     */
    private function upsertItems(array $items): void
    {
        $stmt = $this->getInsertStatement($items[0]);

        $variableItem = clone $items[0];

        foreach (array_keys(get_object_vars($variableItem)) as $column) {
            $stmt->bindParam(
                param: sprintf(":%s", $column),
                var: $variableItem->{$column},
                type: is_int($variableItem->{$column}) === true
                    ? SQLITE3_INTEGER
                    : (is_float($variableItem->{$column}) === true
                        ? SQLITE3_FLOAT
                        : SQLITE3_TEXT),
            );
        }

        foreach ($items as $item) {
            foreach (array_keys(\get_object_vars($variableItem)) as $col) {
                $variableItem->$col = $item->$col;
            }

            if ($stmt->execute() === false) {
                $this->clear();

                throw new SQLite3Exception(
                    message: $this->connection->lastErrorMsg(),
                    code: $this->connection->lastErrorCode(),
                );
            }
        }
    }

    /**
     * Persist items removed from the in-memory collection
     * to the database.
     *
     * @param object[] $items
     */
    private function deleteRemovedItems(array $items): void
    {
        $result = $this->connection->query(
            query: <<<SQL
            SELECT [id] FROM "{$this->tableName}"
            SQL,
        );

        if ($result === false) {
            $this->clear();

            throw new SQLite3Exception(
                message: $this->connection->lastErrorMsg(),
                code: $this->connection->lastErrorCode(),
            );
        }

        $rows = [];

        while (($row = $result->fetchArray(\SQLITE3_ASSOC)) !== false) {
            $rows[] = $row;
        }

        $itemsToRemoveIds = \array_diff(
            \array_column($rows, "id"),
            \array_column($items, "id"),
        );

        $stmt = $this->connection->prepare(
            <<<SQL
            DELETE FROM "{$this->tableName}"
            WHERE [id] IN (:ids)
            SQL
        );

        if ($stmt === false) {
            $this->clear();

            throw new SQLite3Exception(
                message: $this->connection->lastErrorMsg(),
                code: $this->connection->lastErrorCode(),
            );
        }

        if ($stmt->bindValue(":ids", join(", ", $itemsToRemoveIds)) === false) {
            $this->clear();

            throw new SQLite3Exception(
                message: $this->connection->lastErrorMsg(),
                code: $this->connection->lastErrorCode(),
            );
        }

        $stmt->execute();
    }

    /**
     * @throws SQLite3Exception
     */
    private function getInsertStatement(object $item): \SQLite3Stmt
    {
        $columnNames = array_keys(get_object_vars($item));
        $cols = join(", ", array_map(static fn(string $col) => sprintf("[%s]", $col), $columnNames));
        $bindVariables = join(
            ", ",
            array_map(
                static fn(string $col) => sprintf(":%s", $col),
                $columnNames,
            ),
        );
        $colStmts = join(
            ", ",
            array_map(
                static fn(string $col) => sprintf(
                    "[%s]=excluded.[%s]",
                    $col,
                    $col,
                ),
                $columnNames,
            ),
        );
        $stmt = $this->connection->prepare(
            <<<SQL
            INSERT INTO "{$this->tableName}" ({$cols})
            VALUES ({$bindVariables})
            ON CONFLICT([id]) DO UPDATE SET
            {$colStmts};
            SQL
        );

        if ($stmt === false) {
            $this->clear();

            throw new SQLite3Exception(
                message: $this->connection->lastErrorMsg(),
                code: $this->connection->lastErrorCode(),
            );
        }

        return $stmt;
    }

    private function validateColumnNamesOrThrow(object $item): void
    {
        $columnNames = array_keys(get_object_vars($item));
        $patternCheckResult = preg_grep("/^[[:alpha:]_][[:alnum:]_]*$/", $columnNames, PREG_GREP_INVERT);
        if ($patternCheckResult === false || count($patternCheckResult) > 0) {
            $this->clear();
            throw new InvalidColumnNamesException();
        }
    }


    private function validateItemTypeOrThrow(mixed $item): void
    {
        if (is_object($item) === false) {
            $this->clear();
            throw new ItemNotObjectException();
        }

        if (is_a($item, $this->typeClassName) === false) {
            $this->clear();
            throw new ItemClassException(
                typeClassName: $this->typeClassName,
            );
        }
    }
}
