<?php declare(strict_types=1);

namespace LeanPHP\Hasher;

final  readonly class BuiltInPasswordHasher implements HasherInterface
{
    public function __construct(
        private string $algorithm = \PASSWORD_DEFAULT,
        /**
         * @var array<string|int> Values are the password hashing constants
         *
         * @see https://www.php.net/manual/en/password.constants.php
         */
        private array $options = [],
    ) {
    }

    public function hash(string $password): string
    {
        return password_hash($password, $this->algorithm, $this->options);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }
}