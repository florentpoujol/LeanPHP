<?php

declare(strict_types=1);

namespace LeanPHP\Validation2;

abstract class AbstractRule
{
    public function __construct(
        protected null|string $message = null,
        /**
         * @var array<string>
         */
        protected array $groups = [],
    ) {
    }

    public function getMessage(null|string $keyName = null): string
    {
        return $this->message ?? "This validation rule " . static::class . "didn't pass.";
    }

    public function isInGroup(string $group): bool
    {
        return in_array($group, $this->groups, true);
    }

    abstract public function validate(mixed $value): bool;
}