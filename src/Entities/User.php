<?php declare(strict_types=1);

namespace App\Entities;

use LeanPHP\Database\AbstractModel;

final class User extends AbstractModel
{
    public function __construct(
        public ?int $id,
        public string $email,
        public string $password,
        public null|\DateTimeImmutable $email_verified_at,
        public \DateTimeImmutable $created_at,
        public \DateTimeImmutable $updated_at,
    ) {
    }
}