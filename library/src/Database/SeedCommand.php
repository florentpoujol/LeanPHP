<?php declare(strict_types=1);

namespace LeanPHP\Database;

use LeanPHP\Console\InputOutput\AbstractInput;
use LeanPHP\Console\InputOutput\AbstractOutput;

final readonly class SeedCommand
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

    public function run(AbstractInput $input, AbstractOutput $output): int
    {
        $seedFiles = $this->getSeedFiles();

        $output->write("Seeding the database in environment '$this->environmentName'...");

        $fileName = $input->getArguments()[0] ?? null;
        if ($fileName === null) {
            $toExecuteCount = \count($seedFiles);
            $output->write("Found $toExecuteCount seeders to run.");
        } else {
            $output->write('Seeding only the specified seeder.');
        }

        foreach ($seedFiles as $file) {
            $seedName = substr($file, 0, -4); // remove the ".php" or ".sql" suffix

            if ($fileName !== null && $fileName !== $seedName) {
                continue;
            }

            $output->write("Seeding '$seedName'...");
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

            $output->write("Done in $elapsedTimeInMs ms.");
        }

        return 0;
    }
}