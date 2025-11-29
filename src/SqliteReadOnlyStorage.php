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
use Phpolar\SqliteStorage\Exception\{
    NonExistentClassException,
    NonExistentPrimaryKeyAccessorException,
};
use SQLite3;

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
    use SqliteReadTrait;

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
