<?php

declare(strict_types=1);

namespace LeanPHP\Collection;

final class DottedKeyValueStore
{
    public function __construct(
        /**
         * @var array<string, scalar|array<string, mixed>>
         */
        private array $data = [
            // keys are always strings, values can be scalars, or nested array
        ],
    ) {
    }

    public function set(string $key, mixed $value): self
    {
        $segments = explode('.', $key);
        if (\count($segments) === 1) {
            $this->data[$key] = $value; // @phpstan-ignore-line

            return $this;
        }

        $lastSegment = array_pop($segments);
        $data = & $this->data; // REFERENCE

        foreach ($segments as $segment) {
            if (!\array_key_exists($segment, $data)) {
                $data[$segment] = [];
                // or throw an exception because a subset of the key doesn't lead to any value ?
            }

            if (!\is_array($data[$segment])) {
                $data[$segment] = [];
                // or throw an exception to not accidentally override data ?
            }

            $data = & $data[$segment]; // REFERENCE
        }

        $data[$lastSegment] = $value;

        return $this;
    }

    public function unset(string $key): self
    {
        $segments = explode('.', $key);
        if (\count($segments) === 1) {
            unset($this->data[$key]);

            return $this;
        }

        $lastSegment = array_pop($segments);
        $visitedSegments = [];
        $data = & $this->data; // REFERENCE

        foreach ($segments as $segment) {
            if (!\array_key_exists($segment, $data)) {
                // or throw an exception for this case if the user asks for it ?
                return $this;
            }

            $visitedSegments[] = $segment;
            $data = & $data[$segment]; // REFERENCE
            if (!\is_array($data)) {
                $currentSegments = implode('.', $visitedSegments);
                $type = get_debug_type($data);
                throw new \LogicException("Can't unset value for key '$key', because subset '$currentSegments' doesn't lead to an array but a value of type '$type'.");
            }
        }

        unset($data[$lastSegment]);

        return $this;
    }

    //--------------------------------------------------

    private const int VALUE_NOT_FOUND = \PHP_INT_MIN; // this value is arbitrary, it is just there so that we can distinguish it from any other pertinent value.

    private function getRaw(string $key): mixed
    {
        $segments = explode('.', $key);
        $maxIndex = \count($segments) - 1;

        if ($maxIndex === 0) {
            if (!\array_key_exists($key, $this->data)) {
                return self::VALUE_NOT_FOUND;
            }

            return $this->data[$key];
        }

        $visitedSegments = [];
        $data = $this->data;

        foreach ($segments as $i => $segment) {
            \assert(\is_array($data));
            if (!\array_key_exists($segment, $data)) {
                return self::VALUE_NOT_FOUND;
            }

            $visitedSegments[] = $segment;
            $data = $data[$segment];
            if (!\is_array($data) && $i < $maxIndex) {
                // this is bogus because only part of the key points to a value that isn't a scalar
                $currentSegments = implode('.', $visitedSegments);
                $type = get_debug_type($data);
                throw new \LogicException("Subset '$currentSegments' of key '$key' doesn't lead to an array but a value of type '$type'.");
            }
        }

        return $data;
    }

    public function has(string $key): bool
    {
        return $this->getRaw($key) !== self::VALUE_NOT_FOUND;
    }

    /**
     * @return array<string, scalar|array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->getRaw($key);

        if ($value === self::VALUE_NOT_FOUND) {
            return $default;
        }

        return $value;
    }

    //--------------------------------------------------

    /**
     * @return ($default is null ? null|string : string)
     *
     * @throws ValueNotFoundException when the value isn't found and no default value is provided
     */
    public function getString(string $key, null|string $default = '{default is not set}'): null|string
    {
        $value = $this->getRaw($key);

        if ($value === self::VALUE_NOT_FOUND) {
            if ($default === '{default is not set}') {
                throw new ValueNotFoundException("No value found for key '$key', and no default provided.");
            }

            return $default;
        }

        return (string) $value; // @phpstan-ignore-line
    }

    /**
     * @return ($default is null ? null|int : int)
     *
     * @throws ValueNotFoundException when the value isn't found and no default value is provided
     */
    public function getInt(string $key, null|int $default = \PHP_INT_MIN): null|int
    {
        $value = $this->getRaw($key);

        if ($value === self::VALUE_NOT_FOUND) {
            if ($default === \PHP_INT_MIN) {
                throw new ValueNotFoundException("No value found for key '$key', and no default provided.");
            }

            return $default;
        }

        return (int) $value; // @phpstan-ignore-line
    }

    /**
     * @return ($default is null ? null|float : float)
     *
     * @throws ValueNotFoundException when the value isn't found and no default value is provided
     */
    public function getFloat(string $key, null|float $default = \PHP_FLOAT_MIN): null|float
    {
        $value = $this->getRaw($key);

        if ($value === self::VALUE_NOT_FOUND) {
            if ($default === \PHP_FLOAT_MIN) {
                throw new ValueNotFoundException("No value found for key '$key', and no default provided.");
            }

            return $default;
        }

        return (float) $value; // @phpstan-ignore-line
    }

    /**
     * @param null|bool $default
     *
     * @return ($default is null ? null|bool : bool)
     *
     * @throws ValueNotFoundException when the value isn't found and no default value is provided
     */
    public function getBool(string $key, null|bool|string $default = ''): null|bool
    {
        $value = $this->getRaw($key);

        if ($value === self::VALUE_NOT_FOUND) {
            if (\is_string($default)) {
                throw new ValueNotFoundException("No value found for key '$key', and no default provided.");
            }

            return $default;
        }

        return (bool) $value;
    }

    /**
     * @param null|array<mixed> $default
     *
     * @return ($default is null ? null|array<mixed> : array<mixed>)
     *
     * @throws ValueNotFoundException when the value isn't found and no default value is provided
     */
    public function getArray(string $key, null|array $default = ['']): null|array
    {
        $value = $this->getRaw($key);

        if ($value === self::VALUE_NOT_FOUND) {
            if ($default === ['']) {
                throw new ValueNotFoundException("No value found for key '$key', and no default provided.");
            }

            return $default;
        }

        return (array) $value;
    }
}