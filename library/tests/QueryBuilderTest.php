<?php

/** @noinspection SqlResolve */

declare(strict_types=1);

namespace Tests\LeanPHP;

use DateTime;
use LeanPHP\Database\QueryBuilder;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class QueryBuilderTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $createTable = <<<'SQL'
        CREATE TABLE IF NOT EXISTS `test` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `name` TEXT NOT NULL,
          `email` TEXT,
          `created_at` DATETIME
        );
        CREATE UNIQUE INDEX test_name_uindex ON test (name);
        SQL;

        $this->pdo->exec($createTable);
    }

    public function test_insert_single(): void
    {
        // act
        $qb = new QueryBuilder($this->pdo);
        $success = $qb
            ->inTable('test')
            ->insertSingle([
                'name' => 'Florent',
                'email' => 'flo@flo.fr',
                'created_at' => '2021-11-06 21:27:00',
            ]);

        // assert
        self::assertTrue($success);

        $expected = 'INSERT INTO `test` (`name`, `email`, `created_at`) VALUES (?, ?, ?)';
        self::assertSame($expected, $qb->toSql());
        self::assertSame('1', $qb->getPdo()->lastInsertId());

        $statement = $this->pdo->query('SELECT * FROM test');
        self::assertInstanceOf(PDOStatement::class, $statement);

        $entry = $statement->fetch();
        self::assertSame(1, $entry['id']);
        self::assertSame('Florent', $entry['name']);
        self::assertSame('flo@flo.fr', $entry['email']);
        self::assertSame('2021-11-06 21:27:00', $entry['created_at']);
    }

    public function test_insert_many(): void
    {
        // act
        $qb = new QueryBuilder($this->pdo);
        $success = $qb
            ->inTable('test')
            ->insertMany([
                [
                    'name' => 'Florent2',
                    'email' => 'flo@flo2.fr',
                ],
                [
                    'name' => 'Florent3',
                    'email' => 'flo@flo3.fr',
                ],
            ]);

        // assert
        self::assertTrue($success);

        $expected = 'INSERT INTO `test` (`name`, `email`) VALUES (?, ?), (?, ?)';
        self::assertSame($expected, $qb->toSql());

        /** @var array<array<string, mixed>> $entries */
        $entries = $qb->reset()->selectMany();

        self::assertTrue(isset($entries[0]['id']));
        self::assertSame('Florent2', $entries[0]['name']);
        self::assertSame('flo@flo2.fr', $entries[0]['email']);

        self::assertTrue(isset($entries[0]['id']));
        self::assertSame((int) $qb->getPdo()->lastInsertId(), $entries[1]['id']);
        self::assertSame('Florent3', $entries[1]['name']);
        self::assertSame('flo@flo3.fr', $entries[1]['email']);
    }

    public function test_update(): void
    {
        // arrange
        $qb = new QueryBuilder($this->pdo);
        $qb
            ->inTable('test')
            ->insertMany([
                [
                    'name' => 'Florent2',
                    'email' => 'flo@flo2.fr',
                ],
                [
                    'name' => 'Florent3',
                    'email' => 'flo@flo3.fr',
                ],
            ]);

        // act
        $success = $qb
            ->reset()
            ->inTable('test')
            ->where('name', '=', 'Florent2')
            ->update([
                'email' => 'new email',
            ]);

        // assert
        self::assertTrue($success);

        $expected = 'UPDATE `test` SET `email` = ? WHERE `name` = ?';
        self::assertSame($expected, $qb->toSql());

        /** @var array<array<string, mixed>> $entries */
        $entries = $qb->reset()->selectMany();
        self::assertCount(2, $entries);

        self::assertSame('Florent2', $entries[0]['name']);
        self::assertSame('new email', $entries[0]['email']);

        self::assertSame('Florent3', $entries[1]['name']);
        self::assertSame('flo@flo3.fr', $entries[1]['email']);
    }

    public function test_upsert(): void
    {
        // arrange
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM test')->fetchColumn()); // @phpstan-ignore-line

        // act (insert)
        $qb = new QueryBuilder($this->pdo);
        $success = $qb
            ->inTable('test')
            ->upsertSingle([
                'name' => 'Florent',
                'email' => 'flo@flo.fr',
                'created_at' => '2021-11-06 21:27:00',
            ], ['name']);

        // assert
        self::assertTrue($success);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM test')->fetchColumn()); // @phpstan-ignore-line

        $expected = 'INSERT INTO `test` (`name`, `email`, `created_at`) VALUES (?, ?, ?)';
        $expected .= ' ON CONFLICT (`name`) DO UPDATE SET `name` = excluded.`name`, `email` = excluded.`email`, `created_at` = excluded.`created_at`';
        self::assertSame($expected, $qb->toSql());
        self::assertSame('1', $qb->getPdo()->lastInsertId());

        $statement = $this->pdo->query('SELECT * FROM test');
        self::assertInstanceOf(PDOStatement::class, $statement);

        $entry = $statement->fetch();
        self::assertSame(1, $entry['id']);
        self::assertSame('Florent', $entry['name']);
        self::assertSame('flo@flo.fr', $entry['email']);
        self::assertSame('2021-11-06 21:27:00', $entry['created_at']);

        // --------------------------------------------------
        // act (upsert)
        $qb = new QueryBuilder($this->pdo);
        $success = $qb
            ->inTable('test')
            ->upsertSingle([
                'name' => 'Florent',
                'email' => 'flo@flo.fr',
                'created_at' => '2021-11-06 21:27:00',
            ], ['name']);

        // assert
        self::assertTrue($success);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM test')->fetchColumn()); // @phpstan-ignore-line

        $expected = 'INSERT INTO `test` (`name`, `email`, `created_at`) VALUES (?, ?, ?)';
        $expected .= ' ON CONFLICT (`name`) DO UPDATE SET `name` = excluded.`name`, `email` = excluded.`email`, `created_at` = excluded.`created_at`';
        self::assertSame($expected, $qb->toSql());
        self::assertSame('1', $qb->getPdo()->lastInsertId());

        $statement = $this->pdo->query('SELECT * FROM test');
        self::assertInstanceOf(PDOStatement::class, $statement);

        $entry = $statement->fetch();
        self::assertSame(1, $entry['id']);
        self::assertSame('Florent', $entry['name']);
        self::assertSame('flo@flo.fr', $entry['email']);
        self::assertSame('2021-11-06 21:27:00', $entry['created_at']);
    }

    public function test_delete(): void
    {
        // arrange
        $this->test_insert_many();

        // act
        $qb = new QueryBuilder($this->pdo);
        $success = $qb
            ->fromTable('test')
            ->where('email', '=', 'flo@flo3.fr')
            ->delete();

        // assert
        self::assertTrue($success);

        $expected = 'DELETE FROM `test` WHERE `email` = ? ';
        self::assertSame($expected, $qb->toSql());

        /** @var array<array<string, mixed>> $entries */
        $entries = $qb->reset()->selectMany();
        self::assertCount(1, $entries);

        self::assertSame('Florent2', $entries[0]['name']);
        self::assertSame('flo@flo2.fr', $entries[0]['email']);
    }

    // --------------------------------------------------

    private function seedForSelect(): QueryBuilder
    {
        $qb = new QueryBuilder($this->pdo);
        $qb->inTable('test')->insertMany([
            [
                'name' => 'Florent1',
                'email' => 'flo@flo1.fr',
                'created_at' => '2021-09-06 21:27:00',
            ],
            [
                'name' => 'Flo2',
                'email' => 'flo@flo2.fr',
                'created_at' => '2021-10-06 21:27:00',
            ],
            [
                'name' => 'rent3',
                'email' => 'flo@flo3.fr',
                'created_at' => '2021-11-06 21:27:00',
            ],
        ]);

        return $qb;
    }

    public function test_simple_where(): void
    {
        // arrange
        $qb = $this->seedForSelect();

        // act & assert, several times
        // --------------------------------------------------
        /** @var array<string, mixed> $row */
        $row = $qb
            ->reset()
            ->inTable('test')
            ->where('name', '=', 'stuff')
            ->selectSingle();

        $expected = 'SELECT * FROM `test` WHERE `name` = ? LIMIT 1 ';
        self::assertSame($expected, $qb->toSql());

        self::assertNull($row);

        // --------------------------------------------------
        /** @var array<string, mixed> $row */
        $row = $qb
            ->reset()
            ->inTable('test')
            ->where('name', '=', 'rent3')
            ->selectSingle();

        $expected = 'SELECT * FROM `test` WHERE `name` = ? LIMIT 1 ';
        self::assertSame($expected, $qb->toSql());

        self::assertNotNull($row);
        self::assertSame('rent3', $row['name']);
        self::assertSame('flo@flo3.fr', $row['email']);

        // --------------------------------------------------
        /** @var array<string, mixed> $row */
        $row = $qb
            ->reset()
            ->inTable('test')
            ->where('name', '=', 'rent3')
            ->where('email', 'like', '%flo3.fr')
            ->selectSingle();

        $expected = 'SELECT * FROM `test` WHERE `name` = ? AND `email` LIKE ? LIMIT 1 ';
        self::assertSame($expected, $qb->toSql());

        self::assertNotNull($row);
        self::assertSame('rent3', $row['name']);
        self::assertSame('flo@flo3.fr', $row['email']);

        // --------------------------------------------------
        /** @var array<string, mixed> $row */
        $row = $qb
            ->reset()
            ->inTable('test')
            ->where('created_at', '>=', '2021-11-01')
            ->selectSingle();

        $expected = 'SELECT * FROM `test` WHERE `created_at` >= ? LIMIT 1 ';
        self::assertSame($expected, $qb->toSql());

        self::assertNotNull($row);
        self::assertSame('rent3', $row['name']);
        self::assertSame('flo@flo3.fr', $row['email']);

        // --------------------------------------------------
        /** @var array<array<string, mixed>> $rows */
        $rows = $qb
            ->reset()
            ->inTable('test')
            ->whereBetween('created_at', '2021-10-01', '2021-12-01')
            ->selectMany(['name']);

        $expected = 'SELECT `name` FROM `test` WHERE `created_at` BETWEEN ? AND ? ';
        self::assertSame($expected, $qb->toSql());

        self::assertCount(2, $rows);

        self::assertFalse(isset($rows[0]['id']));
        self::assertFalse(isset($rows[0]['email']));
        self::assertFalse(isset($rows[0]['created_at']));

        self::assertSame('Flo2', $rows[0]['name']);
        self::assertSame('rent3', $rows[1]['name']);
    }

    public function test_nested_where(): void
    {
        // arrange
        $qb = $this->seedForSelect();

        // act & assert, several times
        // --------------------------------------------------
        $qb
            ->reset()
            ->inTable('test')
            ->whereGroup(function (QueryBuilder $qb): void {
                $qb
                    ->whereNotIn('stuff', [1, 2])
                    ->orWhereGroup(fn ($qb) => $qb->where('field', '<=', 1)->where('field2', 'not like', 'stuf%'));
            })
            ->where('name', '=', 'stuff');

        try {
            $qb->selectSingle();
        } catch (PDOException $e) {
            // this is oK, some columns in the query do not exist
        }

        $expected = 'SELECT * FROM `test` WHERE (`stuff` NOT IN (?, ?) OR (`field` <= ? AND `field2` NOT LIKE ?)) AND `name` = ? LIMIT 1 ';
        self::assertSame($expected, $qb->toSql());
    }

    public function test_exists(): void
    {
        // arrange
        $qb = $this->seedForSelect();

        // act
        $exists = $qb
            ->reset()
            ->inTable('test')
            ->where('name', '=', 'stuff')
            ->exists();

        // assert
        $expected = 'SELECT EXISTS(SELECT 1 FROM `test` WHERE `name` = ? )';
        self::assertSame($expected, $qb->toSql());

        self::assertFalse($exists);

        // act
        $exists = $qb
            ->reset()
            ->inTable('test')
            ->where('name', '=', 'Florent1')
            ->exists();

        // assert
        $expected = 'SELECT EXISTS(SELECT 1 FROM `test` WHERE `name` = ? )';
        self::assertSame($expected, $qb->toSql());

        self::assertTrue($exists);
    }

    public function test_count(): void
    {
        // arrange
        $qb = $this->seedForSelect();

        // act
        $count = $qb
            ->reset()
            ->inTable('test')
            ->count();

        // assert

        $expected = 'SELECT COUNT(*) as c FROM `test` ';
        self::assertSame($expected, $qb->toSql());

        self::assertSame(3, $count);

        // act
        $count = $qb
            ->reset()
            ->inTable('test')
            ->where('name', '=', 'Florent1')
            ->count();

        // assert
        $expected = 'SELECT COUNT(*) as c FROM `test` WHERE `name` = ? ';
        self::assertSame($expected, $qb->toSql());

        self::assertSame(1, $count);
    }

    public function test_join(): void
    {
        // arrange
        $createTable = <<<'SQL'
        CREATE TABLE IF NOT EXISTS `join_table` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `test_name` TEXT NOT NULL,
          `joined_value` TEXT NOT NULL
        )
        SQL;

        $this->pdo->exec($createTable);

        $qb = new QueryBuilder($this->pdo);
        $qb->inTable('test')->insertMany([
            [
                'name' => 'Florent1',
                'email' => 'flo@flo1.fr',
                'created_at' => '2021-09-06 21:27:00',
            ],
            [
                'name' => 'Flo2',
                'email' => 'flo@flo2.fr',
                'created_at' => '2021-10-06 21:27:00',
            ],
            [
                'name' => 'rent3',
                'email' => 'flo@flo3.fr',
                'created_at' => '2021-11-06 21:27:00',
            ],
        ]);

        $qb->inTable('join_table')
            ->insertMany([
                [
                    'test_name' => 'Flo2',
                    'joined_value' => 'joined_value1',
                ],
                [
                    'test_name' => 'Flo2',
                    'joined_value' => 'joined_value2',
                ],
                [
                    'test_name' => 'rent3',
                    'joined_value' => 'joined_value3',
                ],
            ]);

        // act
        $qb = $qb->new();
        $qb
            ->fromTable('test')
            ->join('join_table', 'jt')
            ->on('jt.test_name', '=', 'test.name')
            ->where('name', '=', 'Flo2');

        // assert
        $expected = 'SELECT * FROM `test` INNER JOIN `join_table` AS `jt` ON `jt`.`test_name` = `test`.`name` WHERE `name` = ? ';
        self::assertSame($expected, $qb->toSql());

        /** @var array<array<string, mixed>> $entries */
        $entries = $qb->selectMany();

        self::assertCount(2, $entries);

        self::assertSame('Flo2', $entries[0]['name']);
        self::assertSame('Flo2', $entries[0]['test_name']);
        self::assertSame('joined_value1', $entries[0]['joined_value']);

        self::assertSame('Flo2', $entries[1]['name']);
        self::assertSame('Flo2', $entries[1]['test_name']);
        self::assertSame('joined_value2', $entries[1]['joined_value']);
    }

    public function test_hydratation(): void
    {
        // act
        $qb = new QueryBuilder($this->pdo);
        $now = date('Y-m-d H:i:s');
        $qb
            ->inTable('test')
            ->insertMany([
                [
                    'name' => 'Florent2',
                    'email' => 'flo@flo2.fr',
                    'created_at' => $now,
                ],
                [
                    'name' => 'Florent3',
                    'email' => 'flo@flo3.fr',
                    'created_at' => $now,
                ],
            ]);

        // assert
        /** @var array<MyEntity> $entries */
        $entries = $qb->reset()
            ->hydrate(MyEntity::class)
            ->selectMany();

        self::assertInstanceOf(MyEntity::class, $entries[0]);
        self::assertSame(1, $entries[0]->getId());
        self::assertSame('Florent2', $entries[0]->name);
        self::assertSame($now, $entries[0]->getCreatedAt()->format('Y-m-d H:i:s'));

        self::assertSame(2, $entries[1]->getId());
        self::assertSame('Florent3', $entries[1]->name);
        self::assertSame($now, $entries[1]->getCreatedAt()->format('Y-m-d H:i:s'));
    }
}

final class MyEntity
{
    private readonly int $id; // @phpstan-ignore-line (prop is never written, only read)
    public string $name;
    private readonly string $createdAt; // @phpstan-ignore-line (prop is never written, only read)

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTime
    {
        return new DateTime($this->createdAt);
    }
}
