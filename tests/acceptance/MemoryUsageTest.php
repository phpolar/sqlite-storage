<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use Phpolar\SqliteStorage\TestClasses\TestClassWithoutPrimaryKey;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SQLite3;
use SQLite3Result;
use SQLite3Stmt;

#[CoversNothing]
final class MemoryUsageTest extends TestCase
{
    private SQLite3&Stub $connectionStub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connectionStub = $this->createStub(SQLite3::class);
        $stmtStub = $this->createStub(SQLite3Stmt::class);
        $resultStub = $this->createStub(SQLite3Result::class);
        $this->connectionStub->method("query")->willReturn($resultStub);
        $this->connectionStub->method("prepare")->willReturn($stmtStub);
        $stmtStub->method("execute")->willReturn($resultStub);
        $stmtStub->method("bindValue")->willReturn(true);
        $resultStub->method("fetchArray")->willReturn(["id" => "1", "name" => "name1"], false, false);
    }

    #[Test]
    #[TestDox("Memory usage for a entire lifecycle shall be below " . PROJECT_MEMORY_USAGE_THRESHOLD . " bytes")]
    public function shallBeBelowThreshold1()
    {
        $totalUsed = -memory_get_usage();

        $sut = new SqliteStorage(
            connection: $this->connectionStub,
            tableName: "table",
            typeClassName: TestClassWithoutPrimaryKey::class,
        );

        $sut->save("1", new TestClassWithoutPrimaryKey(["id" => "1", "name" => "name1"]));

        unset($sut); // destroy

        $totalUsed += memory_get_usage();
        $this->assertGreaterThan(0, $totalUsed);
        $this->assertLessThanOrEqual((int) PROJECT_MEMORY_USAGE_THRESHOLD, $totalUsed);
    }
}
