<?php

declare(strict_types=1);

namespace LeanPHP\Collection;

use TypeError;

/**
 * @template ValueType
 */
final class GenericList
{
    public function __construct(
        /**
         * @var array<ValueType>
         */
        private array $data = [],
        private CollectionValueType $valueType = CollectionValueType::dontCheck,
        /**
         * @var null|class-string<ValueType>
         */
        private ?string $objectFqcn = null,
    ) {
        $this->data = array_values($data);

        if ($valueType === CollectionValueType::fromFirstValue) {
            \assert(isset($data[0]));

            if (\is_object($data[0])) {
                $this->objectFqcn = $data[0]::class;
                $this->valueType = CollectionValueType::objectOfType;
            } else {
                $this->valueType = CollectionValueType::fromValue($data[0]);
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
    public function push(mixed $value): self
    {
        $this->ensureType($value);

        $this->data[] = $value;

        return $this;
    }

    /**
     * @param ValueType $value
     *
     * @return self<ValueType>
     */
    public function unshift(mixed $value): self
    {
        $this->ensureType($value);

        array_unshift($this->data, $value);

        return $this;
    }

    /**
     * @param ValueType $value
     *
     * @return self<ValueType>
     */
    public function addToBeginning(mixed $value): self // let's face it, NO ONE ever remembers if it's shift or unshift...
    {
        return $this->unshift($value);
    }

    /**
     * @see array_splice()
     *
     * @param array<ValueType> $replacements
     *
     * @return self<ValueType>
     */
    public function splice(int $offset, ?int $length, array $replacements = []): self
    {
        foreach ($replacements as $replacement) {
            $this->ensureType($replacement);
        }

        array_splice($this->data, $offset, $length, $replacements);

        return $this;
    }

    //--------------------------------------------------
    // methods that return array<ValueType>

    /**
     * @see array_slice()
     *
     * @return array<ValueType>
     */
    public function slice(int $offset, ?int $length, bool $preserveKeys = false): array
    {
        return \array_slice($this->data, $offset, $length, $preserveKeys);
    }

    /**
     * @see array_filter()
     *
     * @param null|callable(ValueType $value): bool $callback
     *
     * @return array<ValueType>
     */
    public function filter(?callable $callback): array
    {
        // not  passing the mode ensure that only the value is passed to the callback
        return array_filter($this->data, $callback);
    }

    /**
     * @see array_map()
     *
     * @param null|callable(ValueType $value): ValueType $callback
     *
     * @return array<ValueType>
     */
    public function map(?callable $callback): array
    {
        return array_map($callback, $this->data);
    }

    /**
     * @return array<ValueType>
     */
    public function asArray(): array
    {
        return $this->data;
    }

    //--------------------------------------------------
    // methods that return a single value

    /**
     * @return null|ValueType
     */
    public function pop(): mixed
    {
        return array_pop($this->data);
    }

    /**
     * @return null|ValueType
     */
    public function shift(): mixed
    {
        return array_shift($this->data);
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