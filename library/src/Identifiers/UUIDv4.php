<?php

declare(strict_types=1);

namespace LeanPHP\Identifiers;

/**
 * A 16 bytes, purely random identifier.
 */
final class UUIDv4 extends Identifier
{
    protected function generate(): string
    {
        $hex = bin2hex(random_bytes(16));
        $hex[12] = '4';

        $bin = hex2bin($hex);
        \assert(\is_string($bin));

        return $bin;
    }
}
