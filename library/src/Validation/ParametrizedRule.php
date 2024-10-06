<?php declare(strict_types=1);

namespace LeanPHP\Validation;

use Stringable;

/**
 * Rules that needs user-supplied values to be useful
 */
enum ParametrizedRule: string implements RuleEnum
{
    case instanceof = 'instanceof';
    case regex = 'regex';
    case superiorOrEqual = '>=';
    case superior = '>';
    case inferiorOrEqual = '<=';
    case inferior = '<';
    case minLength = 'minLength';
    case maxLength = 'maxLength';
    case length = 'length';
    case equal = '==';
    case strictlyEqual = '===';
    case in = 'in';
    case sameAs = 'sameAs';

    /**
     * @param array<scalar|Stringable>|string $param
     */
    public function with(array|string $param): string
    {
        if (\is_array($param)) {
            $param = implode(',', $param);
        }

        return "$this->value:$param";
    }
}
