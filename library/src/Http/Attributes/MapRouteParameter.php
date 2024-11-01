<?php

declare(strict_types=1);

namespace LeanPHP\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class MapRouteParameter
{
    public function __construct(
        public ?string $uriSegmentName = null,
    ) {
    }
}