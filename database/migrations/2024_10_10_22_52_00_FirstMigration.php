<?php

use LeanPHP\Database\AbstractMigration;

return new class extends AbstractMigration
{
    public function up(): void
    {
        // for a basic SQLite 'users' table
        $this->pdo->exec(<<<SQL
        create table users
        (
            id integer constraint users_pk primary key autoincrement,
            email TEXT not null constraint users_email_unique unique
        );
        SQL);
    }

    public function down(): void
    {
        $this->pdo->exec(<<<SQL
        drop table users;
        SQL);
    }
};