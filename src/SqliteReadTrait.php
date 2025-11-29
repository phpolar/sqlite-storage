<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use SQLite3;
use SQLite3Exception;

/**
 * Automatically load data from the database.
 */
trait SqliteReadTrait
{
    /**
     * The SQLite3 connection to use.
     */
    private readonly SQLite3 $connection;

    /**
     * The class name of the type being stored.
     */
    private readonly string $typeClassName;

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
}
