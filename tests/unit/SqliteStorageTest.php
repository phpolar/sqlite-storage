<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use Phpolar\SqliteStorage\TestClasses\TestClassWithAgeAndHeight;
use Phpolar\SqliteStorage\TestClasses\TestClassWithInvalidProps;
use Phpolar\SqliteStorage\TestClasses\TestClassWithoutPrimaryKey;
use Phpolar\SqliteStorage\TestClasses\TestClassWithPrimaryKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SQLite3;
use SQLite3Exception;
use SQLite3Result;
use SQLite3Stmt;
use stdClass;

#[CoversClass(SqliteStorage::class)]
#[CoversClass(InvalidColumnNamesException::class)]
#[CoversClass(ItemClassException::class)]
#[CoversClass(ItemNotObjectException::class)]
#[CoversClass(NonExistentClassException::class)]
#[CoversClass(NonExistentPrimaryKeyAccessorException::class)]
final class SqliteStorageTest extends TestCase
{
    private SqliteStorage $sut;
    private SQLite3&MockObject $connectionMock;

    protected function setUp(): void
    {
        $this->connectionMock = $this->createMock(SQLite3::class);

        $this->connectionMock
            ->method("query")
            ->willReturn(
                $this->createMock(\SQLite3Result::class),
            );

        $this->sut = new SqliteStorage(
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
        new SqliteStorage(
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
        new SqliteStorage(
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
    #[TestDox("Shall throw an exception when preparing a statement fails during persist")]
    #[TestWith([["id" => "id1", "name" => "name1"]])]
    public function ewijofe(array $data): void
    {
        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method("prepare")
            ->willReturn(false);
        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method("lastErrorMsg")
            ->willReturn("Prepare failed");

        $item1 = new TestClassWithPrimaryKey($data);

        $this->sut->save($item1->id, $item1);

        $this->expectException(SQLite3Exception::class);
        $this->expectExceptionMessage("Prepare failed");

        $this->sut->persist();
    }

    #[Test]
    #[TestDox("Shall throw an exception when trying to persist non-object items")]
    #[TestWith([0x0ABCDEF])]
    #[TestWith(["a string value"])]
    public function ewijofew(string|int $scalarValue): void
    {
        $this->sut->save(1, $scalarValue);

        $this->expectException(ItemNotObjectException::class);
        $this->expectExceptionMessage("The item must be an object");

        $this->sut->persist();
    }

    #[Test]
    #[TestDox("Shall throw an exception when trying to persist items of a non-matching class")]
    public function ewijofewx(): void
    {
        $this->sut->save(1, (object) ["id" => "id1"]);

        $this->expectException(ItemClassException::class);
        $this->expectExceptionMessageMatches(
            "/^The item must be a [[:alnum:]\\\]+$/"
        );

        $this->sut->persist();
    }

    #[Test]
    #[TestDox("Shall throw an exception when one or more column names are invalid")]
    #[TestWith([["id" => 1, "1invalid-name" => "value"]])]
    #[TestWith([["id" => 1, "../" => "value"]])]
    #[TestWith([["id" => 1, ";1=1" => "value"]])]
    #[TestWith([["id" => 1, "--;1=1" => "value"]])]
    public function ewijofewf(array $data): void
    {
        $sut = new SqliteStorage(
            connection: $this->connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithInvalidProps::class,
        );
        $sut->save(1, new TestClassWithInvalidProps($data));
        $this->expectException(InvalidColumnNamesException::class);
        $this->expectExceptionMessage("One or more column names are invalid.");
        $sut->persist();
    }

    #[Test]
    #[TestDox("Shall call bindParam with the expected parameters during persist")]
    #[TestWith([[["id" => "id1", "name" => "name1", "age" => 25, "height" => 5.9]]])]
    #[TestWith([[["id" => "id2", "name" => "name2", "age" => 30, "height" => 6.1]]])]
    public function ewijofewfg(array $data): void
    {
        $sut = new SqliteStorage(
            connection: $this->connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithAgeAndHeight::class
        );

        /**
         * @var array<int,array<string,string>> $data
         */
        foreach ($data as $item) {
            $sut->save($item["id"], new TestClassWithAgeAndHeight($item));
        }

        $stmtMock = $this->createMock(\SQLite3Stmt::class);
        $this->connectionMock
            ->expects($this->exactly(2))
            ->method("prepare")
            ->withAnyParameters()
            ->willReturn($stmtMock);

        $stmtMock
            ->expects($this->exactly(count(array_keys($data[0])) * count($data)))
            ->method("bindParam")
            ->withAnyParameters();

        $stmtMock
            ->expects($this->once())
            ->method("bindValue")
            ->willReturn(true);

        $stmtMock
            ->expects($this->atLeast(count($data)))
            ->method("execute")
            ->willReturn($this->createMock(SQLite3Result::class));

        $sut->persist();
        $sut->clear();
    }

    #[Test]
    #[TestDox("Shall throw an exception when preparing a statement fails during persist")]
    #[TestWith([["id" => "id1", "name" => "name1"]])]
    public function ewijofewfgh(array $data): void
    {
        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method("prepare")
            ->willReturn(false);
        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method("lastErrorMsg")
            ->willReturn("Execute failed");

        $item1 = new TestClassWithPrimaryKey($data);
        $this->sut->save($item1->id, $item1);
        $this->expectException(Sqlite3Exception::class);
        $this->expectExceptionMessage("Execute failed");

        $this->sut->persist();
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
    #[TestDox("Shall throw a runtime exception when executing the statement fails")]
    #[TestWith([["id" => "id1", "name" => "name1"]])]
    public function fjiods(array $data): void
    {
        $stmtMock = $this->createMock(SQLite3Stmt::class);
        $connectionMock = $this->createMock(SQLite3::class);
        $stmtMock
            ->expects($this->once())
            ->method("execute")
            ->willReturn(false);
        $connectionMock
            ->method("query")
            ->willReturn($this->createStub(SQLite3Result::class));
        $connectionMock
            ->expects($this->once())
            ->method("prepare")
            ->willReturn(
                $stmtMock,
            );
        $connectionMock
            ->expects($this->atLeastOnce())
            ->method("lastErrorMsg")
            ->willReturn("Execute failed");
        $sut = new SqliteStorage(
            connection: $connectionMock,
            tableName: "test_table",
            typeClassName: TestClassWithPrimaryKey::class,
        );

        $item = new TestClassWithPrimaryKey($data);

        $sut->save($item->id, $item);

        $this->expectException(Sqlite3Exception::class);
        $this->expectExceptionMessage("Execute failed");

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
    #[TestDox("Shall not persist items if the connection is readonly")]
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

        unset($sut); // triggers call to persist
    }
}
