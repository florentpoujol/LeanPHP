<?php

declare(strict_types=1);

namespace LeanPHP\Validation;

use LeanPHP\Http\ServerRequest;

final readonly class ServerRequestValidator
{
    public function __construct(
        private ServerRequest $request,
        public Validator $validator,
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

    public function validate(): bool
    {
        $this->validator
            ->setData($this->request->getBodyAsArray())
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