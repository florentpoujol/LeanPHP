<?php

declare(strict_types=1);

namespace LeanPHP\Validation\Form;

use LeanPHP\Validation\ServerRequestEntityValidator;

/**
 * @template T of object
 */
abstract class EntityFormValidator extends FormValidator
{
    public function __construct(
        public readonly ServerRequestEntityValidator $validator,
    ) {
        $validator
            ->setEntityFqcn($this->getEntityFqcn());
    }

    /**
     * @return class-string<T>
     */
    abstract protected function getEntityFqcn(): string;

    /**
     * @return T
     */
    public function getEntity(): object
    {
        return $this->validator->getValidatedEntity(); // @phpstan-ignore-line (... should return T of object but returns object. ...)
    }

    /**
     * @inheritDoc
     */
    protected function getRules(): array
    {
        return $this->validator->validator->getRulesForClassProperties($this->getEntityFqcn());
    }
}