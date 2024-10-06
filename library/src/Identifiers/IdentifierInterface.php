<?php declare(strict_types=1);

namespace LeanPHP\Identifiers;

use UnexpectedValueException;

interface IdentifierInterface
{
    /**
     * @return string Return the raw binary string of the identifier
     */
    public function getRaw(): string;

    /**
     * @return string Return the identifier as hexadecimal string
     */
    public function getHex(): string;

    /**
     * @return string Return a 16 bytes identifier formatted as a UUID with hyphen separator
     *
     * @throws UnexpectedValueException when the identifier is not 16 bytes (32 hex chars)
     */
    public function getUuid(): string;

    /**
     * @param string $id Any hexadecimal string, that may contain hyphen separators
     */
    public static function fromString(string $id): static;
}
