<?php

use LeanPHP\Database\AbstractMigration;

return new class extends AbstractMigration
{
    public function up(): void
    {
        // for a basic SQLite 'users' table
        $this->pdo->exec(<<<SQL
        alter table users add password text not null;
        alter table users add email_verified_at timestamp null;
        SQL);
    }

    public function down(): void
    {
        $this->pdo->exec(<<<SQL
        alter table users drop column password;
        alter table users drop column email_verified_at;
        SQL);
    }
};