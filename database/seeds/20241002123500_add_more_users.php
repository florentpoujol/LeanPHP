<?php

use LeanPHP\Database\AbstractSeed;

return new class extends AbstractSeed
{
    public function run(): void
    {
        // TODO later: use entity factories and/or a faker package here, otherwise it's a little pointless
        $this->pdo->exec(<<<SQL
        insert into users(email) values
        ('test4@example.com')
        SQL);
    }
};