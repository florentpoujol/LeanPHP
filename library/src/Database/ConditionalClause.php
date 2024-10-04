<?php declare(strict_types=1);

namespace LeanPHP\Database;


final class ConditionalClause
{
    public string $condition = 'AND';
    public string $expression = '';
}
