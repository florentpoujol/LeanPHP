<?php declare(strict_types=1);

namespace LeanPHP\EntityHydrator;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class DataToPropertyMap
{
    public function __construct(
        /**
         * @var array<string, string> Keys are the data keys, values are the property names
         */
        public array $map,
    ) {
    }
}