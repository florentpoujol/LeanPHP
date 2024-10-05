<?php

use App\Factories\UserFactory;
use LeanPHP\Database\AbstractSeeder;

return new class extends AbstractSeeder
{
    public function run(): void
    {
        $factory = $this->getFactory(UserFactory::class);
        $factory->saveMany(4);
    }
};