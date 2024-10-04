<?php declare(strict_types=1);

namespace LeanPHP\Database;


final class ConditionalGroup
{
    public string $condition = 'AND';

    /**
     * @var array<ConditionalClause|ConditionalGroup>
     */
    public array $clauses = [];
}
