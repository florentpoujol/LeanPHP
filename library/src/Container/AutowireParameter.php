<?php

declare(strict_types=1);

namespace LeanPHP\Container;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class AutowireParameter
{
    public function __construct(
        public string $paramName,
    ) {
    }
}