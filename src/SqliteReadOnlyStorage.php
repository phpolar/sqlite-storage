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
final class SqliteReadOnlyStorage extends AbstractStorage implements
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
        /**
         * intentionally empty
         * this is a read only connection
         * so we will not be persisting
         * anything to the database
         */
    }
}
