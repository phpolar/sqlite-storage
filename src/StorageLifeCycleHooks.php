<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use Phpolar\Storage\Closable;
use Phpolar\Storage\DestroyHook;
use Phpolar\Storage\InitHook;
use Phpolar\Storage\Loadable;
use Phpolar\Storage\Persistable;

/**
 * Configures life cycle hooks for the storage context.
 */
final readonly class StorageLifeCycleHooks implements InitHook, DestroyHook
{
    public function __construct(
        private Closable & Loadable & Persistable $storage,
    ) {
    }

    /**
     * Configures methods that should be executed
     * when the storage context is initialized.
     */
    public function onInit(): void
    {
        $this->storage->load();
    }

    /**
     * Configures methods that should be executed
     * when the storge context is destroyed.
     */
    public function onDestroy(): void
    {
        $this->storage->persist();
        $this->storage->close();
    }
}
