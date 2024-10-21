<?php

declare(strict_types=1);

namespace LeanPHP\Validation;

/**
 * @template T of object
 */
abstract class FormValidator
{
    public function __construct(
        public readonly ServerRequestEntityValidator $validator,
    ) {
        $validator
            ->setEntityFqcn($this->getEntityFqcn())
            ->validateOrThrow();
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
}