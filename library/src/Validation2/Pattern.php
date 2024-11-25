<?php

declare(strict_types=1);

namespace LeanPHP\Validation2;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
final class Pattern extends AbstractRule
{
    /**
     * @param array<string> $groups
     */
    public function __construct(
        private string $pattern,
        null|string $message = null,
        array $groups = [],
    )   {
        parent::__construct(
            $message ?? "The value doesn't match the provided regex pattern '$pattern'",
            $groups,
        );
    }

    public function validate(mixed $value): bool
    {
        return \is_string($value) && preg_match($this->pattern, $value) === 1;
    }
}