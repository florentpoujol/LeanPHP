<?php declare(strict_types=1);

namespace App\Factories;

use App\Entities\User;
use LeanPHP\Database\AbstractModelFactory;

/**
 * @extends AbstractModelFactory<User>
 */
final class UserFactory extends AbstractModelFactory
{
    /**
     * @return class-string<User>
     */
    protected function getModelFqcn(): string
    {
        return User::class;
    }

    /**
     * @inheritDoc
     */
    protected function getDatabaseRowData(): array
    {
        return [
            'id' => null,
            'email' => uniqid('', true) . '@example.com',
            'password' => password_hash('test4', \PASSWORD_BCRYPT),
            'email_verified_at' => null,
            'created_at' => (new \DateTimeImmutable())->format(\DATE_ATOM),
            'updated_at' => (new \DateTimeImmutable())->format(\DATE_ATOM),
        ];
    }
}