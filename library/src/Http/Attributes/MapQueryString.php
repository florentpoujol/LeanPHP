<?php

declare(strict_types=1);

namespace LeanPHP\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class MapQueryString
{
    public function __construct(
        public ?string $queryStringName = null,
        public bool $validate = true,
    ) {
    }
}