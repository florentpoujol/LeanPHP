<?php declare(strict_types=1);

namespace LeanPHP\Validation;

use UnexpectedValueException;

final class ValidationException extends UnexpectedValueException
{
    public function __construct(
        /** @var array<string, mixed>|object An assoc array, or an object */
        public array|object $data,

        /** @var array<string, array<string>> The keys match the one found in the values, values are the message(s) for each key */
        public array $messages,
    ) {
        parent::__construct("ValidationException: some data doesn't pass the provided validation rules.");
    }
}
