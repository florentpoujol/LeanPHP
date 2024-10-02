<?php declare(strict_types=1);

namespace LeanPHP\Database;

final readonly class RunMigrationsCommand
{
    public function __construct(
        private \PDO $pdo,
        private string $migrationTableName = 'leanphp_migrations',
        private string $migrationFolder = __DIR__ . '/../../../database/migrations', // FIXME default migration path should depend on a "baseAppPath" or assume the lib is in the vendor folder
        private string $environmentName = 'prod',
    ) {
        $migrationTableRegex = '/^[a-z0-9_-]{4,20}$/i';
        if (preg_match($migrationTableRegex, $this->migrationTableName) !== 1) {
            throw new \UnexpectedValueException("The migration table name '$this->migrationTableName' doesn't match the regex '$migrationTableRegex'.");
        }
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

        echo "Running migrations in environment '$this->environmentName'..." . \PHP_EOL;
        $toExecuteCount = \count($migrationFiles) - \count($alreadyExecutedMigrationNames);
        echo "Found $toExecuteCount migrations to run." . \PHP_EOL;

        foreach ($migrationFiles as $file) {
            $migrationName = substr($file, 0, -4); // remove the ".php" or ".sql" suffix

            if (\in_array($migrationName, $alreadyExecutedMigrationNames, true)) {
                continue;
            }

            echo "Starting migration '$migrationName'..." . \PHP_EOL;
            $startTime = microtime(true);

            if (str_ends_with($file, '.php')) {
                /** @var AbstractMigration $migrationInstance */
                $migrationInstance = require_once $this->migrationFolder . '/' . $file;
                $migrationInstance->setPdo($this->pdo);
                $migrationInstance->up();
            } else {
                $sql = file_get_contents($this->migrationFolder . '/' . $file);
                \assert(\is_string($sql));
                $this->pdo->query($sql);
            }

            // migration successful, register it
            $statement = $this->pdo->prepare(<<<SQL
            insert into `$this->migrationTableName` (name) values (:migrationName)
            SQL);
            \assert($statement instanceof \PDOStatement);
            $statement->bindParam('migrationName', $migrationName);
            $statement->execute();

            $endTime = microtime(true);
            $elapsedTimeInMs = number_format(($endTime - $startTime) * 1_000);

            echo "Migration '$migrationName' done in $elapsedTimeInMs ms." . \PHP_EOL;
        }

        return 0;
    }
}