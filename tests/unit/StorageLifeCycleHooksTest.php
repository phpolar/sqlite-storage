<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use Phpolar\Storage\Closable;
use Phpolar\Storage\Loadable;
use Phpolar\Storage\Persistable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(StorageLifeCycleHooks::class)]
final class StorageLifeCycleHooksTest extends TestCase
{
    #[Test]
    #[TestDox("Shall call load on initialization")]
    public function ewijof(): void
    {
        /**
         * @var Persistable&Loadable&Closable&MockObject $storageMock
         */
        $storageMock = $this->createMockForIntersectionOfInterfaces([Persistable::class, Loadable::class, Closable::class]);
        $storageMock->expects($this->once())
            ->method('load');

        $hooks = new StorageLifeCycleHooks($storageMock);
        $hooks->onInit();
    }

    #[Test]
    #[TestDox("Shall call persist on destruction")]
    public function weijof(): void
    {
        /**
         * @var Persistable&Loadable&Closable&MockObject $storageMock
         */
        $storageMock = $this->createMockForIntersectionOfInterfaces([Persistable::class, Loadable::class, Closable::class]);
        $storageMock
            ->expects($this->once())
            ->method('persist');

        $hooks = new StorageLifeCycleHooks($storageMock);
        $hooks->onDestroy();
    }

    #[Test]
    #[TestDox("Shall call close on destruction")]
    public function efwpkqo(): void
    {
        /**
         * @var Persistable&Loadable&Closable&MockObject $storageMock
         */
        $storageMock = $this->createMockForIntersectionOfInterfaces([Persistable::class, Loadable::class, Closable::class]);
        $storageMock
            ->expects($this->once())
            ->method('close');

        $hooks = new StorageLifeCycleHooks($storageMock);
        $hooks->onDestroy();
    }
}
