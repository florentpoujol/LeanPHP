<?php

declare(strict_types=1);

namespace LeanPHP;

// dottedkeyvaluestore
final readonly class ConfigRepository
{
    public function __construct(
        /** @var array<string, null|bool|int|float|string|array<mixed>|object> */
        private array $data,
    ) {
    }

    public static function makeFromFolder(): self
    {
        // read folder
        // override config of main folder with env based subfolders
        $data = [];

        return new self($data);
    }

    // --------------------------------------------------

    public function has(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * @return array<string, null|bool|int|float|string|array<mixed>|object>
     */
    public function getData(): array
    {
        return $this->data;
    }

    // --------------------------------------------------

    public function getStringOrThrow(string $name): string
    {
        if (!$this->has($name)) {
            throw new \UnexpectedValueException("Config key '$name' is not set.");
        }

        return (string) $this->data[$name];
    }

    public function getStringOrNull(string $name): ?string
    {
        if (!$this->has($name)) {
            return null;
        }

        return (string) $this->data[$name];
    }

    public function getStringOrDefault(string $name, string $default): string
    {
        if (!$this->has($name)) {
            return $default;
        }

        return (string) $this->data[$name];
    }

    // --------------------------------------------------

    public function getIntOrThrow(string $name): int
    {
        if (!$this->has($name)) {
            throw new \UnexpectedValueException("Config key '$name' is not set.");
        }

        return (int) $this->data[$name];
    }

    public function getIntOrNull(string $name): ?int
    {
        if (!$this->has($name)) {
            return null;
        }

        return (int) $this->data[$name];
    }

    public function getIntOrDefault(string $name, int $default): int
    {
        if (!$this->has($name)) {
            return $default;
        }

        return (int) $this->data[$name];
    }

    // --------------------------------------------------

    public function getFloatOrThrow(string $name): float
    {
        if (!$this->has($name)) {
            throw new \UnexpectedValueException("Config key '$name' is not set.");
        }

        return (float) $this->data[$name];
    }

    public function getFloatOrNull(string $name): ?float
    {
        if (!$this->has($name)) {
            return null;
        }

        return (float) $this->data[$name];
    }

    public function getFloatOrDefault(string $name, float $default): float
    {
        if (!$this->has($name)) {
            return $default;
        }

        return (float) $this->data[$name];
    }

    // --------------------------------------------------

    public function getBoolOrThrow(string $name): bool
    {
        if (!$this->has($name)) {
            throw new \UnexpectedValueException("Config key '$name' is not set.");
        }

        return (bool) $this->data[$name];
    }

    public function getBoolOrNull(string $name): ?bool
    {
        if (!$this->has($name)) {
            return null;
        }

        return (bool) $this->data[$name];
    }

    public function getBoolOrDefault(string $name, bool $default): bool
    {
        if (!$this->has($name)) {
            return $default;
        }

        return (bool) $this->data[$name];
    }

    // --------------------------------------------------

    /**
     * @return array<mixed>
     */
    public function getArrayOrThrow(string $name): array
    {
        if (!$this->has($name)) {
            throw new \UnexpectedValueException("Config key '$name' is not set.");
        }

        return (array) $this->data[$name];
    }

    /**
     * @return null|array<mixed>
     */
    public function getArrayOrNull(string $name): ?array
    {
        if (!$this->has($name)) {
            return null;
        }

        return (array) $this->data[$name];
    }

    /**
     * @param array<mixed> $default
     *
     * @return array<mixed>
     */
    public function getArrayOrDefault(string $name, array $default): array
    {
        if (!$this->has($name)) {
            return $default;
        }

        return (array) $this->data[$name];
    }

    // --------------------------------------------------

    public function getObjectOrThrow(string $name): object
    {
        if (!$this->has($name)) {
            throw new \UnexpectedValueException("Config key '$name' is not set.");
        }

        return (object) $this->data[$name];
    }

    public function getObjectOrNull(string $name): ?object
    {
        if (!$this->has($name)) {
            return null;
        }

        return (object) $this->data[$name];
    }

    public function getObjectOrDefault(string $name, object $default): object
    {
        if (!$this->has($name)) {
            return $default;
        }

        return (object) $this->data[$name];
    }
}
