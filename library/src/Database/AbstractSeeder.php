<?php declare(strict_types=1);

namespace LeanPHP\Database;

abstract class AbstractSeeder
{
    protected \PDO $pdo;

    public function setPdo(\PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    abstract public function run(): void;
}