<?php declare(strict_types=1);

namespace App\Entities;

final readonly class LoginFormData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe,
    ) {
    }
}