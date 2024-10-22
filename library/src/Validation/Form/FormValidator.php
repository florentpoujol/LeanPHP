<?php

declare(strict_types=1);

namespace LeanPHP\Validation\Form;

use LeanPHP\Validation\Rule;
use LeanPHP\Validation\RuleEnum;
use LeanPHP\Validation\RuleInterface;

abstract class FormValidator
{
    /**
     * @return array<string, array<string|callable|RuleEnum|RuleInterface>>
     */
    abstract protected function getRules(): array;

    /**
     * @var array<string, array<string|callable|RuleEnum|RuleInterface>>
     */
    private array $rules = [];

    //--------------------------------------------------
    // front-end validation

    /**
     * @param array<string> $excludeAttributes
     */
    public function getHtmlValidationAttrs(string $fieldName, array $excludeAttributes = []): string
    {
        if ($this->rules === []) {
            $this->rules = $this->getRules();
        }


        $rules = $this->rules[$fieldName] ?? null;
        if ($rules === null || $rules === []) {
            return '';
        }


        $attributes = [];
        if (\in_array(Rule::notNull->value, $rules, true)) {
            $attributes['required'] = '';
        }

        if (isset($rules['maxlength']) && $rules['type'] === 'string') {
            $attributes['maxlength'] = $rules['maxlength'];
        }
        if (isset($rules['minlength']) && $rules['type'] === 'string') {
            $attributes['minlength'] = $rules['minlength'];
        }

        if (isset($rules['maxlength']) && $rules['type'] !== 'string') {
            $attributes['max'] = $rules['maxlength'];
        }
        if (isset($rules['minlength']) && $rules['type'] !== 'string') {
            $attributes['min'] = $rules['minlength'];
        }

        if (isset($rules['regex'])) {
            $attributes['pattern'] = trim($rules['regex'], '/');
        }

        $strAttributes = '';
        foreach ($attributes as $key => $value) {
            if (\in_array($key, $excludeAttributes, true)) {
                continue;
            }

            $strAttributes .= " $key";
            if ($value !== '') {
                \assert(\is_scalar($value));
                $strAttributes .= "=\"$value\"";
            }
        }

        return $strAttributes;
    }
}