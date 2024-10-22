<?php

declare(strict_types=1);

namespace LeanPHP\Validation;

final class FormHtmlValidationBuilder
{
    public function __construct(
        private readonly Validator $validator,
    ) {
    }

    /**
     * @var array<string, array<string|callable|RuleEnum|RuleInterface>>
     */
    private array $rules = [];

    /**
     * @param array<string, array<string|callable|RuleEnum|RuleInterface>> $rules
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @param class-string<object> $fqcn
     */
    public function setEntityFqcn(string $fqcn): self
    {
        $this->rules = $this->validator->getRulesForClassProperties($fqcn);

        return $this;
    }

    //--------------------------------------------------
    // front-end validation

    /**
     * @param array<string> $excludeAttributes
     */
    public function getHtmlValidationAttrs(string $fieldName, array $excludeAttributes = []): string
    {
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
            $attributes['pattern'] = trim($rules['regex'], '/'); // @phpstan-ignore-line (can not cast ... to string)
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