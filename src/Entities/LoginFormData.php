<?php declare(strict_types=1);

namespace App\Entities;

use LeanPHP\Validation\Rule;
use LeanPHP\Validation\Validates;

final readonly class LoginFormData
{
    public function __construct(
        #[Validates([Rule::email, 'maxlength' => 255, 'regex' => '/[0-9]+/'])]
        public string $email,
        public string $password,
        public bool $rememberMe,
    ) {
    }
}