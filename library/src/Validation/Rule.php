<?php declare(strict_types=1);

namespace LeanPHP\Validation;

enum Rule: string implements RuleEnum
{
    // misc
    case date = 'date';
    case email = 'email';
    case exists = 'exists';
    case notNull = 'not-null';
    case optional = 'optional';
    case uuid = 'uuid';

    // is_*
    case array = 'array';
    case bool = 'bool';
    case callable = 'callable';
    case countable = 'countable';
    case float = 'float';
    case int = 'int';
    case iterable = 'iterable';
    case null = 'null';
    case numeric = 'numeric';
    case object = 'object';
    case resource = 'resource';
    case scalar = 'scalar';
    case string = 'string';
}
