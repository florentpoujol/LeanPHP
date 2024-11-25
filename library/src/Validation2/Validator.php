<?php

declare(strict_types=1);

namespace LeanPHP\Validation2;

final class Validator
{
    /**
     * @var array<AbstractRule>|array<string, AbstractRule>
     */
    private array $rules = [];

    /**
     * @var scalar|array|object
     */
    private mixed $data;

    public function __construct(
    ) {
    }

    /**
     * @param array<AbstractRule>|array<string, AbstractRule> $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * @param scalar|array<string, mixed>|object $data
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string> array of error messages
     */
    public function validate(): array
    {
        $errorMessages = [];

        if (\is_scalar($this->data)) {
            \assert(array_is_list($this->rules));

            foreach ($this->rules as $rule) {
                if (! $rule->validate($this->data)) {
                    $errorMessages[] = $rule->getMessage();
                }
            }

            return $errorMessages;
        }

        if (\is_array($this->data)) {
            foreach ($this->data as $key => $value) {
                $rules = (array) ($this->rules[$key] ?? []);

                foreach ($rules as $rule) {
                    if (!$rule->validate($value)) {
                        $errorMessages[] = $rule->getMessage($key);
                    }
                }
            }

            return $errorMessages;
        }

        if (\is_object($this->data)) {
            if ($this->rules === []) {
                // instrospect rules from attributes
            }

        }
    }
}