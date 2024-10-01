<?php declare(strict_types=1);

namespace LeanPHP\Database;

final readonly class RunMigrationsCommand
{
    private string $migrationFolder;

    public function __construct(
        private \PDO $pdo,
        private string $migrationTableName = 'leanphp_migrations',
    ) {
        $migrationTableRegex = '/^[a-z0-9_-]{4,20}$/i';
        if (preg_match($migrationTableRegex, $this->migrationTableName) !== 1) {
            throw new \UnexpectedValueException("The migration table name '$this->migrationTableName' doesn't match the regex '$migrationTableRegex'.");
        }

        $this->migrationFolder = __DIR__ . '/../../../database/migrations';
    }

    /**
     * @return array<string>
     */
    private function getMigrationFiles(): array
    {
        // already sorted alphabetically, which sorts the migration by date asc (oldest first) if they are prefixed by a date or timestamp
        $files = scandir($this->migrationFolder);
        \assert(\is_array($files));

        return \array_slice($files, 2);
    }

    private function createMigrationTable(): void
    {
        $this->pdo->exec(<<<SQL
        create table `$this->migrationTableName` (
            name varchar not null constraint migrations_pk primary key,
            executed_at timestamp default current_timestamp not null
        );
        SQL);
    }

    /**
     * @return array<string>
     */
    private function getAlreadyExecutedMigrationNames(): array
    {
        try {
            $statement = $this->pdo->query("select name from `$this->migrationTableName`;");
            \assert($statement instanceof \PDOStatement);

            $data = $statement->fetchAll();
            \assert(\is_array($data));

            $data = array_map(fn (array $migration) => $migration['name'], $data);
        } catch (\PDOException $e) {
            $message = $e->getMessage();
            if (str_contains($message, $this->migrationTableName)) {
                $this->createMigrationTable();
                $data = [];
            } else {
                throw $e;
            }
        }

        return $data;
    }

    public function run(): int
    {
        $alreadyExecutedMigrationNames = $this->getAlreadyExecutedMigrationNames();
        $migrationFiles = $this->getMigrationFiles();

        foreach ($migrationFiles as $file) {
            $migrationName = substr($file, 0, -4);

            if (\in_array($migrationName, $alreadyExecutedMigrationNames, true)) {
                continue;
            }

            /** @var AbstractMigration $migrationInstance */
            $migrationInstance = require_once $this->migrationFolder . '/' . $file;
            $migrationInstance->setPdo($this->pdo);
            $migrationInstance->up();
            echo "migration up " . \PHP_EOL;

            // migration successful, register it
            $statement = $this->pdo->prepare(<<<SQL
            insert into `$this->migrationTableName` (name) values (:migrationName)
            SQL);
            \assert($statement instanceof \PDOStatement);
            $statement->bindParam('migrationName', $migrationName);
            $statement->execute();
            echo "migration saved " . \PHP_EOL;
        }

        return 0;
    }
}