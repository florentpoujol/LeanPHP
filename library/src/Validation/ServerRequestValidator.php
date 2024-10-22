<?php

declare(strict_types=1);

namespace LeanPHP\Validation;

use LeanPHP\Http\ServerRequest;

final class ServerRequestValidator
{
    public function __construct(
        private readonly ServerRequest $request,
        public readonly Validator $validator,
    ) {
    }

    /**
     * @param array<string, array<string|callable|RuleEnum|RuleInterface>> $rules
     */
    public function setRules(array $rules): self
    {
        $this->validator->setRules($rules);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidatedData(): array
    {
        $data = $this->validator->getValidatedData();
        \assert(\is_array($data));

        return $data;
    }

    //--------------------------------------------------

    /**
     * @var null|class-string<object>
     */
    private ?string $entityFqcn = null;

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
        if ($this->entityFqcn !== null) {
            $data = $this->request->hydrateBodyAsOne($this->entityFqcn);
            $this->entity = $data;
        } else {
            $data = $this->request->getBodyAsArray();
        }

        $this->validator
            ->setData($data)
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
            $this->validator->getData(),
            $this->validator->getMessages(),
        );
    }
}