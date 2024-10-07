<?php

use App\Factories\UserFactory;
use LeanPHP\Database\AbstractSeeder;

return new class extends AbstractSeeder
{
    public function run(): void
    {
        $factory = $this->getFactory(UserFactory::class);
        $factory->saveMany(4, [[
            'email' => 'test4@example.com',
            'password' => $this->container->get(\LeanPHP\Hasher\HasherInterface::class)->hash('test4'),
        ]]);
    }
};