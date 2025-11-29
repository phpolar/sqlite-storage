<?php

declare(strict_types=1);

namespace Phpolar\SqliteStorage;

use Exception;
use Phpolar\SqliteStorage\TestClasses\TestClassWithPrimaryKey;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use SQLite3;
use SQLite3Result;
use SQLite3Stmt;

#[CoversNothing]
#[RunTestsInSeparateProcesses]
final class SqliteReadOnlyStorageIntegrationTest extends TestCase
{
    private const DB_FILE_NAME = __DIR__ . \DIRECTORY_SEPARATOR . "integration-test.db";
    private const TABLE_NAME = "integration_test_data";
    private const DATA = [
        ["id" => "id1", "name" => "name1"],
        ["id" => "id2", "name" => "name2"],
        ["id" => "id3", "name" => "name3"],
        ["id" => "id4", "name" => "name4"],
        ["id" => "id5", "name" => "name5"],
    ];

    private SQLite3 $realConnection;
    private SqliteReadOnlyStorage $sut;


    protected function setUp(): void
    {
        parent::setUp();
        if (\file_exists(self::DB_FILE_NAME) === true) {
            \unlink(self::DB_FILE_NAME);
        }
        $this->realConnection = new SQLite3(self::DB_FILE_NAME);
        $this->createTable();
        $this->insertData();

        $this->sut = new SqliteReadOnlyStorage(
            connection: new SQLite3(
                filename: self::DB_FILE_NAME,
                flags: \SQLITE3_OPEN_READONLY,
            ),
            tableName: self::TABLE_NAME,
            typeClassName: TestClassWithPrimaryKey::class,
        );
    }

    private function createTable(): void
    {
        $tableName = self::TABLE_NAME;
        $this->realConnection->exec(
            <<<SQL
            CREATE TABLE IF NOT EXISTS "$tableName"
            (
                [id] TEXT,
                [name] TEXT,
                PRIMARY KEY([id] ASC)
            )
            SQL
        );
    }

    private function insertData(): void
    {
        $tableName = self::TABLE_NAME;
        $id = "";
        $name = "";
        $stmt = $this->realConnection->prepare(
            <<<SQL
            INSERT INTO "$tableName" ([id], [name]) VALUES (:id, :name)
            SQL
        );

        if ($stmt === false) {
            throw new Exception("Preparing the insert statement failed.");
        }

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":name", $name);

        foreach (self::DATA as ["id" => $id, "name" => $name]) {
            $stmt->execute();
        }
    }

    #[Test]
    #[TestDox("Shall load data from the database")]
    public function dfsijoa()
    {
        /**
         * @var TestClassWithPrimaryKey
         */
        $result = $this->sut->find("id1")->tryUnwrap();

        $this->assertInstanceOf(TestClassWithPrimaryKey::class, $result);
        $this->assertSame($result->name, "name1");
        /**
         * @var TestClassWithPrimaryKey
         */
        $result = $this->sut->find("id2")->tryUnwrap();

        $this->assertInstanceOf(TestClassWithPrimaryKey::class, $result);
        $this->assertSame($result->name, "name2");
        /**
         * @var TestClassWithPrimaryKey
         */
        $result = $this->sut->find("id3")->tryUnwrap();

        $this->assertInstanceOf(TestClassWithPrimaryKey::class, $result);
        $this->assertSame($result->name, "name3");
        /**
         * @var TestClassWithPrimaryKey
         */
        $result = $this->sut->find("id4")->tryUnwrap();

        $this->assertInstanceOf(TestClassWithPrimaryKey::class, $result);
        $this->assertSame($result->name, "name4");
        /**
         * @var TestClassWithPrimaryKey
         */
        $result = $this->sut->find("id5")->tryUnwrap();

        $this->assertInstanceOf(TestClassWithPrimaryKey::class, $result);
        $this->assertSame($result->name, "name5");
    }

    #[Test]
    #[TestDox("Shall not persist items to the database")]
    #[TestWith([["id" => "id1", "name" => "replacement_name"], "id1", "replacement_name", self::TABLE_NAME])]
    public function dfsijoxds(array $data, string $expectedId, string $expectedName, string $tableName)
    {
        $sut = new SqliteReadOnlyStorage(
            connection: new SQLite3(
                filename: self::DB_FILE_NAME,
                flags: \SQLITE3_OPEN_READONLY,
            ),
            tableName: self::TABLE_NAME,
            typeClassName: TestClassWithPrimaryKey::class,
        );
        $item1 = new TestClassWithPrimaryKey($data);
        $sut->replace($item1->getPrimaryKey(), $item1);

        // persist items to database
        unset($sut);
        \gc_collect_cycles();

        $connection = new SQLite3(
            filename: self::DB_FILE_NAME,
            flags: \SQLITE3_OPEN_READONLY
        );

        $stmt = $connection->prepare(
            <<<SQL
            SELECT * FROM "{$tableName}" WHERE [id]=:id
            SQL
        );


        $this->assertInstanceOf(SQLite3Stmt::class, $stmt);

        if ($stmt === false) {
            throw new Exception("Was not caught by assertion.");
        }

        $stmt->bindValue(":id", $item1->getPrimaryKey());

        $result = $stmt->execute();

        $this->assertInstanceOf(SQLite3Result::class, $result);

        if ($result === false) {
            throw new Exception("Was not caught by assertion.");
        }

        $row = $result->fetchArray(\SQLITE3_ASSOC);

        $this->assertIsArray($row);

        $this->assertSame($row["id"], $expectedId);
        $this->assertNotSame($row["name"], $expectedName);
    }
}
