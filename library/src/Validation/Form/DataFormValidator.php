<?php

declare(strict_types=1);

namespace LeanPHP\Validation\Form;

use LeanPHP\Validation\RuleEnum;
use LeanPHP\Validation\RuleInterface;
use LeanPHP\Validation\ServerRequestValidator;

abstract class DataFormValidator extends FormValidator
{
    public function __construct(
        public readonly ServerRequestValidator $validator,
    ) {
        $validator
            ->setRules($this->getRules());
    }

    /**
     * @return array<string, array<string|callable|RuleEnum|RuleInterface>>
     */
    abstract protected function getRules(): array;

    /**
     * @return array<string, mixed>
     */
    public function getValidatedData(): array
    {
        return $this->validator->getValidatedData();
    }
}