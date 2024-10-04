<?php declare(strict_types=1);

namespace LeanPHP\EntityHydrator;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DataToPropertyMap
{
    public function __construct(
        /**
         * @var array<string, string> Keys are data keys, values are property names
         */
        public array $map,
    ) {
    }
}