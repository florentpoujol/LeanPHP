<?php declare(strict_types=1);

namespace LeanPHP\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Validates
{
    public function __construct(
        /** @var array<string|int, scalar|Rule> $rules */
        public array $rules,
    ) {
    }
}
