<?php declare(strict_types=1);

namespace App\Factories;

use App\Entities\User;
use LeanPHP\Database\AbstractModelFactory;
use LeanPHP\Database\QueryBuilder;
use LeanPHP\Hasher\HasherInterface;

/**
 * @extends AbstractModelFactory<User>
 */
final class UserFactory extends AbstractModelFactory
{
    /**
     * @param QueryBuilder<User> $queryBuilder
     */
    public function __construct(
        \PDO $pdo,
        QueryBuilder $queryBuilder,
        private readonly HasherInterface $hasher,
    )
    {
        parent::__construct($pdo, $queryBuilder);
    }

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
        $email = uniqid('', true);

        return [
            'id' => null,
            'email' => $email . '@example.com',
            'password' => $this->hasher->hash($email),
            'email_verified_at' => null,
            'created_at' => (new \DateTimeImmutable())->format(\DATE_ATOM),
            'updated_at' => (new \DateTimeImmutable())->format(\DATE_ATOM),
        ];
    }
}