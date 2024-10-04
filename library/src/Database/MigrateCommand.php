<?php declare(strict_types=1);

namespace LeanPHP\Database;

use LeanPHP\Console\InputOutput\AbstractInput;
use LeanPHP\Console\InputOutput\AbstractOutput;
use LeanPHP\Console\InputOutput\TerminalDisplayCode;

final readonly class MigrateCommand
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

    private AbstractOutput $output; // @phpstan-ignore-line (ask to assign it in constructor)

    public function run(AbstractInput $input, AbstractOutput $output): int
    {
        $this->output = $output; // @phpstan-ignore-line (ask to assign it in constructor)

        $command = $input->getArguments()[0] ?? null;

        switch ($command) {
            case 'fresh':
                $this->migrateFresh();
                break;
            case 'up':
                $this->migrateUp();
                break;
            case 'down':
                // $this->migrateDown();
                break;
            default: throw new \UnexpectedValueException("Unknown first argument '$command', should be 'up' or 'down'.");
        }

        return 0;
    }

    // Note : doesn't make sens for that method to be there,
    // should be in the input class or a base command class
    private function promptConfirm(string $question = 'Confirm ? [y/n]'): bool
    {
        $this->output->write($question);

        $returned = fgets(\STDIN); // this is blocking, and await an input from the terminal
        \assert(\is_string($returned));

        return strtolower($returned[0]) === 'y';
    }

    private function migrateFresh(): void
    {
        // delete all tables, or the whole db
        $tables = [];

        $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $statement = $this->pdo->query("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'");
            \assert($statement instanceof \PDOStatement);
            $statement->setFetchMode(\PDO::FETCH_COLUMN, 0);
            $tables = $statement->fetchAll(0);
        } elseif ($driver === 'pgsql') {
            $this->pdo->query('\dt');
            $tables = [];
        } elseif ($driver === 'mysql') {
            $this->pdo->query('show tables;');
        }

        \assert(\is_array($tables));
        $tableCount = \count($tables);

        $message = TerminalDisplayCode::getDecoratedString(
            "This will delete $tableCount tables and all data in the database " .
            "for environment '$this->environmentName'. \nAre you sure ? [y/n]",
            [TerminalDisplayCode::BG_RED],
        );
        if (! $this->promptConfirm($message)) {
            $this->output->writeSuccess('Cancelled');

            return;
        }

        $this->output->writeError("Dropping $tableCount tables.");

        foreach ($tables as $table) {
            $this->pdo->exec("drop table `$table`;");
        }

        $this->migrateUp();
    }

    private function migrateUp(): void
    {
        $alreadyExecutedMigrationNames = $this->getAlreadyExecutedMigrationNames();
        $migrationFiles = $this->getMigrationFiles();

        $this->output->write("Running migrations in environment '$this->environmentName'...");
        $toExecuteCount = \count($migrationFiles) - \count($alreadyExecutedMigrationNames);
        $this->output->write("Found $toExecuteCount migrations to run.");

        foreach ($migrationFiles as $file) {
            $migrationName = substr($file, 0, -4); // remove the ".php" or ".sql" suffix

            if (\in_array($migrationName, $alreadyExecutedMigrationNames, true)) {
                continue;
            }

            $this->output->write("Starting migration '$migrationName'...");
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

            $this->output->write("Done in $elapsedTimeInMs ms.");
        }

        $this->output->writeSuccess('Migrations done.');
    }
}