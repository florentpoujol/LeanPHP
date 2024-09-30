<?php

declare(strict_types=1);

namespace LeanPHP;

final readonly class Environment
{
    public static function readFileIfExists(
        string $filePath,
        string $envVarPattern = '/\s*(?<key>[A-Z0-9_-]+)\s*=(?<value>.+)\n?$/iU', // eg: SOME_ENV = "a value"
    ): void {
        if (! file_exists($filePath)) {
            return;
        }

        $fileContent = file_get_contents($filePath);
        \assert(\is_string($fileContent));
        $matches = [];

        preg_match_all($envVarPattern, $fileContent, $matches);

        foreach ($matches['key'] as $i => $key) {
            $value = trim($matches['value'][$i]);
            if (
                \strlen($value) > 2
                && (
                    ('"' === $value[0] && '"' === $value[-1])
                    || ("'" === $value[0] && "'" === $value[-1])
                )
            ) {
                // if the value is surrounded by a quotation mark, remove it, but only that one
                // so that a value like ""test"" become "test"
                $value = substr($value, 1, -1);
            }

            putenv("$key=$value");
        }
    }

    // --------------------------------------------------

    public static function getStringOrThrow(string $name): string
    {
        $value = getenv($name);
        if (false === $value) {
            throw new \UnexpectedValueException("Environment variable '$name' is not set.");
        }

        return $value;
    }

    public static function getStringOrNull(string $name): ?string
    {
        $value = getenv($name);
        if (false === $value) {
            return null;
        }

        return $value;
    }

    public static function getStringOrDefault(string $name, string $default): string
    {
        $value = getenv($name);
        if (false === $value) {
            return $default;
        }

        return $value;
    }

    // --------------------------------------------------

    public static function getIntOrThrow(string $name): int
    {
        $value = getenv($name);
        if (false === $value) {
            throw new \UnexpectedValueException("Environment variable '$name' is not set.");
        }

        return (int) $value;
    }

    public static function getIntOrNull(string $name): ?int
    {
        $value = getenv($name);
        if (false === $value) {
            return null;
        }

        return (int) $value;
    }

    public static function getIntOrDefault(string $name, int $default): int
    {
        $value = getenv($name);
        if (false === $value) {
            return $default;
        }

        return (int) $value;
    }

    // --------------------------------------------------

    public static function getFloatOrThrow(string $name): float
    {
        $value = getenv($name);
        if (false === $value) {
            throw new \UnexpectedValueException("Environment variable '$name' is not set.");
        }

        return (float) $value;
    }

    public static function getFloatOrNull(string $name): ?float
    {
        $value = getenv($name);
        if (false === $value) {
            return null;
        }

        return (float) $value;
    }

    public static function getFloatOrDefault(string $name, float $default): float
    {
        $value = getenv($name);
        if (false === $value) {
            return $default;
        }

        return (float) $value;
    }

    // --------------------------------------------------

    public static function getBoolOrThrow(string $name): bool
    {
        $value = getenv($name);
        if (false === $value) {
            throw new \UnexpectedValueException("Environment variable '$name' is not set.");
        }

        return (bool) $value;
    }

    public static function getBoolOrNull(string $name): ?bool
    {
        $value = getenv($name);
        if (false === $value) {
            return null;
        }

        return (bool) $value;
    }

    public static function getBoolOrDefault(string $name, bool $default): bool
    {
        $value = getenv($name);
        if (false === $value) {
            return $default;
        }

        return (bool) $value;
    }
}
