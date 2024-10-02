<?php declare(strict_types=1);

namespace LeanPHP\Database;

final readonly class RunSeedersCommand
{
    public function __construct(
        private \PDO   $pdo,
        private string $seedsFolder = __DIR__ . '/../../../database/seeders', // FIXME default seeders path should depend on a "baseAppPath" or assume the lib is in the vendor folder
        private string $environmentName = 'prod',
    ) {
    }

    /**
     * @return array<string>
     */
    private function getSeedFiles(): array
    {
        // already sorted alphabetically, which sorts the seed by date asc (oldest first) if they are prefixed by a date or timestamp
        $files = scandir($this->seedsFolder);
        \assert(\is_array($files));

        return \array_slice($files, 2); // remove "." and ".."
    }

    public function run(null|string $fileName = null): int
    {
        $seedFiles = $this->getSeedFiles();

        echo "Seeding the database in environment '$this->environmentName'..." . \PHP_EOL;

        if ($fileName === null) {
            $toExecuteCount = \count($seedFiles);
            echo "Found $toExecuteCount seeders to run." . \PHP_EOL;
        }

        foreach ($seedFiles as $file) {
            $seedName = substr($file, 0, -4); // remove the ".php" or ".sql" suffix

            if ($fileName !== null && $fileName !== $seedName) {
                continue;
            }

            echo "Seeding '$seedName'..." . \PHP_EOL;
            $startTime = microtime(true);

            if (str_ends_with($file, '.php')) {
                /** @var AbstractSeeder $seedInstance */
                $seedInstance = require_once $this->seedsFolder . '/' . $file;
                $seedInstance->setPdo($this->pdo);
                $seedInstance->run();
            } else {
                $sql = file_get_contents($this->seedsFolder . '/' . $file);
                \assert(\is_string($sql));
                $this->pdo->query($sql);
            }

            $endTime = microtime(true);
            $elapsedTimeInMs = number_format(($endTime - $startTime) * 1_000);

            echo "Done in $elapsedTimeInMs ms." . \PHP_EOL;
        }

        return 0;
    }
}