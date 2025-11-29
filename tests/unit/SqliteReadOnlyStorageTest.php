<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use Phpolar\SqliteStorage\TestClasses\TestClassWithoutPrimaryKey;
use Phpolar\SqliteStorage\TestClasses\TestClassWithPrimaryKey;
use Phpolar\SqliteStorage\Exception\{
    NonExistentClassException,
    NonExistentPrimaryKeyAccessorException,
};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SQLite3;
use SQLite3Exception;
use SQLite3Result;
use SQLite3Stmt;
use stdClass;

#[CoversClass(SqliteReadOnlyStorage::class)]
#[CoversClass(StorageLifeCycleHooks::class)]
#[CoversClass(NonExistentClassException::class)]
#[CoversClass(NonExistentPrimaryKeyAccessorException::class)]
#[CoversTrait(SqliteReadTrait::class)]
#[UsesClass(SqliteStorage::class)]
final class SqliteReadOnlyStorageTest extends TestCase
{
    private SqliteReadOnlyStorage $sut;
    private SQLite3&MockObject $connectionMock;

    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(SQLite3::class);

        $this->connectionMock
            ->method("query")
            ->willReturn(
                $this->createMock(\SQLite3Result::class),
            );

        $this->sut = new SqliteReadOnlyStorage(
            connection: $this->connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );
    }

    protected function tearDown(): void
    {
        // clear the storage after each test
        $this->sut->clear();
    }

    #[Test]
    #[TestDox("Shall throw an exception when the provided class name does not exist")]
    public function ewijofj(): void
    {
        $this->expectException(NonExistentClassException::class);
        $this->expectExceptionMessageMatches(
            "/^The class [[:alnum:]\\\]+ does not exist.$/"
        );
        new SqliteReadOnlyStorage(
            connection: $this->createMock(SQLite3::class),
            tableName: "test_table",
            typeClassName: "NonExistentClass",
        );
    }

    #[Test]
    #[TestDox("Shall throw an exception when the provided class name does not exist")]
    public function ewijofja(): void
    {
        $this->expectException(NonExistentPrimaryKeyAccessorException::class);
        $this->expectExceptionMessageMatches(
            "/^The class [[:alnum:]\\\]+ should have either a 'getPrimaryKey' method or an 'id' property.$/"
        );
        new SqliteReadOnlyStorage(
            connection: $this->createMock(SQLite3::class),
            tableName: "test_table",
            typeClassName: stdClass::class,
        );
    }

    #[Test]
    #[TestDox("Shall close the connection without errors")]
    public function ewijof(): void
    {
        $this->connectionMock
            ->expects($this->once())
            ->method("close");

        $this->sut->close();
    }

    #[Test]
    #[TestDox("Shall use the expected query during load")]
    #[TestWith(["SELECT * FROM \"test_table\";"])]
    public function ewijofewfghj(string $expectedQuery): void
    {
        $this->connectionMock
            ->expects($this->once())
            ->method("query")
            ->with($expectedQuery)
            ->willReturn($this->createMock(\SQLite3Result::class));

        $this->sut->load();
    }

    #[Test]
    #[TestDox("Shall load items for classes with primary key methods into the storage during load")]
    public function ewijofewfghjkl(): void
    {
        $connectionMock = $this->createMock(SQLite3::class);
        $resultMock = $this->createMock(SQLite3Result::class);
        $connectionMock
            ->expects($this->atLeastOnce())
            ->method("query")
            ->willReturn($resultMock);
        $resultMock
            ->expects($this->atLeast(3))
            ->method("fetchArray")
            ->with(SQLITE3_ASSOC)
            ->willReturn(
                ["id" => "id1", "name" => "name1"],
                ["id" => "id2", "name" => "name2"],
                false,
                false,
            );

        $sut = new SqliteStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );


        $sut->load();
        $this->assertCount(2, $sut);



        /**
         * @var object
         */
        $item1 = $sut->find("id1")->tryUnwrap();
        $this->assertNotNull($item1);
        $this->assertEquals("name1", $item1->name);

        /**
         * @var object
         */
        $item2 = $sut->find("id2")->tryUnwrap();
        $this->assertNotNull($item2);
        $this->assertEquals("name2", $item2->name);

        $sut->clear();
    }

    #[Test]
    #[TestDox("Shall load items for classes without primary key methods into the storage during load")]
    public function ewijofewfghjklz(): void
    {
        $connectionMock = $this->createMock(SQLite3::class);
        $resultMock = $this->createMock(SQLite3Result::class);
        $connectionMock
            ->expects($this->atLeastOnce())
            ->method("query")
            ->willReturn($resultMock);
        $resultMock
            ->expects($this->atLeast(3))
            ->method("fetchArray")
            ->with(SQLITE3_ASSOC)
            ->willReturn(
                ["id" => "id1", "name" => "name1"],
                ["id" => "id2", "name" => "name2"],
                false,
                false,
            );

        $sut = new SqliteStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithoutPrimaryKey::class,
        );


        $sut->load();
        $this->assertCount(2, $sut);


        /**
         * @var object
         */
        $item1 = $sut->find("id1")->tryUnwrap();
        $this->assertNotNull($item1);
        $this->assertEquals("name1", $item1->name);

        /**
         * @var object
         */
        $item2 = $sut->find("id2")->tryUnwrap();
        $this->assertNotNull($item2);
        $this->assertEquals("name2", $item2->name);

        // cleaning up here because the destructor will be called
        // before the tearDown method
        $sut->clear();
    }

    #[Test]
    #[TestDox("Shall short circuit when there are no items")]
    public function dfsijoa(): void
    {
        $connectionMock = $this->createMock(SQLite3::class);
        $connectionMock
            ->expects($this->never())
            ->method("prepare");
        $connectionMock
            ->method("query")
            ->willReturn($this->createStub(SQLite3Result::class));
        $sut = new SqliteStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithoutPrimaryKey::class,
        );
        $this->assertCount(0, $sut);
        $sut->persist();
    }

    #[Test]
    #[TestDox("Shall throw an exception querying the database fails during load")]
    public function ewijofewfghjk(): void
    {
        $connectionMock = $this->createMock(SQLite3::class);
        $connectionMock
            ->expects($this->atLeastOnce())
            ->method("query")
            ->willReturn(
                $this->createStub(SQLite3Result::class),
                false,
            );
        $connectionMock
            ->method("prepare")
            ->willReturn(
                $this->createMock(SQLite3Stmt::class),
            );
        $connectionMock
            ->expects($this->atLeastOnce())
            ->method("lastErrorMsg")
            ->willReturn("Query failed");

        $sut = new SqliteStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        $this->expectException(Sqlite3Exception::class);
        $this->expectExceptionMessage("Query failed");

        $sut->load();
    }

    #[Test]
    #[TestDox("Shall not persist items")]
    #[TestWith([["id" => "id1", "name" => "name1"]])]
    public function dfsijo(array $data)
    {
        $connectionMock = $this->createMock(SQLite3::class);
        $stmtMock = $this->createMock(SQLite3Stmt::class);
        $resultStub = $this->createStub(SQLite3Result::class);
        $resultStub
            ->method("fetchArray")
            ->willReturn(
                $data,
                false
            );
        $stmtMock
            ->expects($this->never())
            ->method("bindParam");
        $stmtMock
            ->expects($this->never())
            ->method("execute");
        $connectionMock
            ->method("query")
            ->willReturn($resultStub);
        $connectionMock
            ->method("prepare")
            ->willReturn($stmtMock);

        $sut = new SqliteReadOnlyStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        $sut->persist();
    }
}
