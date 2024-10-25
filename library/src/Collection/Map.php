<?php

declare(strict_types=1);

namespace LeanPHP\Collection;

use TypeError;

/**
 * @template ValueType
 */
final class Map
{
    public function __construct(
        /**
         * @var array<string, ValueType>
         */
        private array $data = [],
        private CollectionValueType $valueType = CollectionValueType::dontCheck,
        /**
         * @var null|class-string<ValueType>
         */
        private null|string $objectFqcn = null,
    ) {
        if ($valueType === CollectionValueType::fromFirstValue) {
            $value = current($data);
            if (\is_object($value)) {
                $this->objectFqcn = $value::class;
                $this->valueType = CollectionValueType::objectOfType;
            } else {
                $this->valueType = CollectionValueType::fromValue($value);
            }
        }

        if ($this->valueType !== CollectionValueType::dontCheck) {
            foreach ($this->data as $datum) {
                $this->ensureType($datum);
            }
        }
    }

    /**
     * @throws TypeError
     */
    private function ensureType(mixed $value): void
    {
        if ($this->objectFqcn !== null) {
            $this->valueType->ensureType($value);

            return;
        }

        if (! ($value instanceof $this->objectFqcn)) {
            $type = get_debug_type($value);
            throw new TypeError("Value is of type '$type' instead of the expected object of type '$this->objectFqcn'");
        }
    }

    //--------------------------------------------------
    // functions that modify the array (that return self<ValueType>)

    /**
     * @param ValueType $value
     *
     * @return self<ValueType>
     */
    public function set(string $key, mixed $value): self
    {
        $this->ensureType($value);

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @return self<ValueType>
     */
    public function unset(string $key): self
    {
        unset($this->data[$key]);

        return $this;
    }

    //--------------------------------------------------
    // methods that return array<string, ValueType>

    /**
     * @see array_filter()
     *
     * @param null|callable(ValueType $value, string $key): bool $callback
     *
     * @return array<string, ValueType>
     */
    public function filter(?callable $callback): array
    {
        return array_filter($this->data, $callback, \ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @see array_map()
     *
     * @param callable(string $key, ValueType $value): ValueType $callback
     *
     * @return array<string, ValueType>
     */
    public function map(callable $callback): array
    {
        $newData = [];
        foreach ($this->data as $key => $value) {
            $newData[$key] = $callback($key, $value);
        }

        return $newData;
    }

    /**
     * @return array<string, ValueType>
     */
    public function asArray(): array
    {
        return $this->data;
    }

    //--------------------------------------------------

    /**
     * @param array<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return ('array_' . $name)(...$arguments); // @phpstan-ignore-line (trying to invoke non falsy string but it might not be a callable)
    }
}