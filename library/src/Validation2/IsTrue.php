<?php

declare(strict_types=1);

namespace LeanPHP\Validation2;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_PROPERTY|Attribute::TARGET_PARAMETER)]
final class IsTrue extends AbstractRule
{
    /**
     * @param array<string> $groups
     */
    public function __construct(
        null|string $message = null,
        array $groups = [],
    )   {
        parent::__construct(
            $message ?? "The value isn't true",
            $groups,
        );
    }

    public function validate(mixed $value): bool
    {
        return $value === true;
    }
}