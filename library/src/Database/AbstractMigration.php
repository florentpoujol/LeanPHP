<?php declare(strict_types=1);

namespace LeanPHP\Database;

abstract class AbstractMigration
{
    protected \PDO $pdo;

    public function setPdo(\PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    abstract public function up(): void;
    abstract public function down(): void;
}