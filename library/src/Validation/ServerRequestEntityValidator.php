<?php

declare(strict_types=1);

namespace LeanPHP\Validation;

use LeanPHP\Http\ServerRequest;

final class ServerRequestEntityValidator
{
    public function __construct(
        private readonly ServerRequest $request,
        public readonly Validator $validator,
    ) {
    }

    /**
     * @var class-string<object>
     */
    private string $entityFqcn;
    private object $entity;

    /**
     * @param class-string<object> $entityFqcn
     */
    public function setEntityFqcn(string $entityFqcn): self
    {
        $this->entityFqcn = $entityFqcn;

        return $this;
    }

    public function getValidatedEntity(): object
    {
        if ($this->validator->isValid()) {
            return $this->entity;
        }

        throw new \LogicException("can't access the entity if not validated");
    }

    //--------------------------------------------------

    public function validate(): bool
    {
        $this->entity = $this->request->hydrateBodyAsOne($this->entityFqcn);

        $this->validator
            ->setData($this->entity)
            ->validate();

        if ($this->validator->isValid()) {
            return true;
        }

        $this->request
            ->getSessionOrNull()
            ?->setData('validation_errors', $this->validator->getMessages());

        return false;
    }

    /**
     * @return true|never
     *
     * @throws ValidationException
     */
    public function validateOrThrow(): true
    {
        if ($this->validate()) {
            return true;
        }

        throw new ValidationException(
            $this->entity,
            $this->validator->getMessages(),
        );
    }
}