<?php declare(strict_types=1);

namespace App\Entities;

use LeanPHP\Validation\Rule;
use LeanPHP\Validation\Validates;

final readonly class LoginFormData
{
    public function __construct(
        #[Validates([Rule::email, 'maxLength' => 255])]
        public string $email,
        public string $password,
        public bool $rememberMe,
    ) {
    }
}