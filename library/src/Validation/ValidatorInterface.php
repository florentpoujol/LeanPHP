<?php declare(strict_types=1);

namespace LeanPHP\Validation;

use stdClass;

interface ValidatorInterface
{
    /**
     * @param array<string, array<string|callable|RuleEnum|RuleInterface>> $rules
     */
    public function setRules(array $rules): self;

    public function isValid(): bool;

    /**
     * @throws ValidationException if some data isn't valid
     */
    public function throwIfNotValid(): self;

    /**
     * @return array<string, array<string>>
     */
    public function getMessages(): array;

    /**
     * @param array<string> $exclude The validated keys to exclude from the returned data
     *
     * @return array<string, mixed>|stdClass
     *
     * @throws ValidationException if some data isn't valid
     */
    public function getValidatedData(array $exclude = []): array|stdClass;

    public function validate(): self;

    /**
     * @return array<string, mixed>|object
     */
    public function getData(): array|object;
}