<?php

declare(strict_types=1);

namespace App\Http;

use App\Entities\LoginFormData;
use LeanPHP\Validation\FormValidator;

/**
 * @extends FormValidator<LoginFormData>
 */
final class LoginForm extends FormValidator
{
    protected function getEntityFqcn(): string
    {
        return LoginFormData::class;
    }
}
