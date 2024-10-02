<?php declare(strict_types=1);

namespace LeanPHP\Console\InputOutput;

abstract class AbstractInput
{
    public function __construct(
        /**
         * @var array<string> $arguments
         */
        protected array $arguments,

        /**
         * @var array<string, null|string|array<string>>
         */
        protected array $options,
    ) {
    }

    /**
     * @return array<string>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return array<string, null|string|array<string>>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $name): bool
    {
        return \array_key_exists($name, $this->options);
    }

    /**
     * @return string|array<string>
     */
    public function getOptionOrThrow(string $name): string|array
    {
        if (!\array_key_exists($name, $this->options)) {
            throw new \UnexpectedValueException("Option '$name' isn't set.");
        }

        $value = $this->options[$name];
        \assert($value !== null);

        return $value;
    }

    /**
     * @return string|array<string>|null
     */
    public function getOptionOrNull(string $name): null|string|array
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @return string|array<string>
     */
    public function getOptionOrDefault(string $name, string $default): string|array
    {
        return $this->options[$name] ?? $default;
    }
}