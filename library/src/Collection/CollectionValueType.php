<?php

declare(strict_types=1);

namespace LeanPHP\Collection;

use TypeError;

enum CollectionValueType
{
    case dontCheck;
    case fromFirstValue;

    case int;
    case float;
    case bool;
    case array;

    case string;
    case nonEmptyString;
    case classString;

    case object;
    case objectOfType;

    public static function fromValue(mixed $value): self
    {
        return match (\gettype($value)) { // purposefully using gettype() instead of get_debug_type()
            'boolean' => self::bool,
            'integer' => self::int,
            'double' => self::float,
            'string' => self::string,
            'array' => self::array,
            'object' => self::object,
            default => throw new \UnexpectedValueException(
                "Passed value is of type '" . get_debug_type($value) . "' instead of one of the scalar, array or object.",
            ),
        };
    }

    /**
     * @throws TypeError
     */
    public function ensureType(mixed $value): void
    {
        if ($this === self::dontCheck) {
            return;
        }

        $actualType = get_debug_type($value);

        $expectedType = match($this) {
            self::int => 'int',
            self::float => 'float',
            self::bool => 'bool',
            self::string => 'string',
            self::object => 'object',
            self::array => 'array',
            default => null,
        };

        if ($expectedType !== null) {
            if ($actualType !== $expectedType) {
                throw new TypeError("Value is of type '$actualType' instead of '$expectedType'.");
            }

            return;
        }

        if ($this === self::nonEmptyString && $value === '') {
            throw new TypeError('Value is an empty string instead of a non empty string.');
        }

        if (
            $this === self::classString &&
            (
                !\is_string($value) ||
                (
                    !class_exists($value) || !interface_exists($value) || !enum_exists($value)
                )
            )
        ) {
            throw new TypeError('Value is not a string, or doesn\'t match any known class, interface or enum FQCN.');
        }

        if ($this === self::object && !\is_object($value)) {
            throw new TypeError("Value is of type '$actualType' instead of 'object'.");
        }

        if ($this === self::fromFirstValue) {
            throw new \LogicException("Don't call ensureType() on CollectionValueType::fromFirstValue.");
        }
    }
}