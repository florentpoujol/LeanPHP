<?php

declare(strict_types=1);

namespace App\Http;

use App\Entities\LoginFormData;
use LeanPHP\Validation\Form\EntityFormValidator;

/**
 * @extends EntityFormValidator<LoginFormData>
 */
final class LoginForm extends EntityFormValidator
{
    protected function getEntityFqcn(): string
    {
        return LoginFormData::class;
    }
}
