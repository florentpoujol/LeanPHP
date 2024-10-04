<?php declare(strict_types=1);

namespace LeanPHP\Database;

use LeanPHP\Container;
use LeanPHP\EntityHydrator\EntityHydrator;
use LeanPHP\EntityHydrator\EntityHydratorInterface;
use PDO;
use Stringable;

/**
 * @template HydratedEntityType of object
 */
final class QueryBuilder
{
    private static ?PDO $_pdo = null;

    public static function make(string $table = null): self
    {
        if (self::$_pdo === null) {
            self::$_pdo = Container::self()->get(PDO::class);
        }

        $qb = new self(self::$_pdo, new EntityHydrator());

        if ($table !== null) {
            $qb->table($table);
        }

        return $qb;
    }

    public function __construct(
        private readonly PDO $pdo,
        private readonly ?EntityHydratorInterface $entityHydrator = null,
    ) {
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function new(): self
    {
        return (new self($this->pdo, $this->entityHydrator))->inTable($this->table);
        // for some reason with SQLite memory, this seems to reset the connexion, and thus the DB ?
    }

    public function reset(): self
    {
        $this->action = '';

        $this->rawSelects = [];

        $this->fields = [];
        $this->insertedRowCount = 0;
        $this->upsertKeys = [];

        $this->join = [];
        $this->lastJoinId = -1;
        $this->onClauses = [];

        $this->whereClauses = [];
        $this->whereBindings = [];

        $this->orderBy = [];
        $this->groupBy = [];
        $this->limit = '';
        $this->offset = '';

        $this->havingClauses = [];
        $this->havingBindings = [];

        return $this;
    }

    public const string ACTION_INSERT = 'INSERT';
    public const string ACTION_UPSERT = 'UPSERT'; // insert or update
    public const string ACTION_UPDATE = 'UPDATE';
    public const string ACTION_DELETE = 'DELETE';
    public const string ACTION_SELECT = 'SElECT';
    public const string ACTION_EXISTS = 'EXISTS';

    private string $action = self::ACTION_SELECT;

    // --------------------------------------------------
    // select

    /**
     * @var array<string>
     */
    private array $rawSelects = [];

    public function selectRaw(string $rawSelect): self
    {
        $this->rawSelects[] = $rawSelect;

        return $this;
    }

    /**
     * @param array<string>|string $fields
     *
     * @return array<array<string, bool|int|string>>|array<HydratedEntityType>
     */
    public function selectMany(array|string $fields = []): array
    {
        $this->action = self::ACTION_SELECT;
        $this->fields = (array) $fields;

        $statement = $this->pdo->prepare($this->toSql());
        $statement->execute(array_merge(
            $this->whereBindings,
            $this->havingBindings,
        ));

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === false) {
            $strFields = "'" . implode("', '", (array) $fields) . "'";
            throw new \Exception("Select request for fields $strFields could not be performed.");
        }

        if ($rows === []) {
            return [];
        }

        if ($this->hydrateEntityFqcn === null) {
            return $rows;
        }

        // This is actually checked in the hydrate method
        // so in practice we know this is the case when the fqcn is not null.
        \assert($this->entityHydrator instanceof EntityHydratorInterface);

        return $this->entityHydrator->hydrateMany($rows, $this->hydrateEntityFqcn);
    }

    /**
     * @param array<string>|string $fields
     *
     * @return null|array<string, bool|int|string>|HydratedEntityType
     */
    public function selectSingle(array|string $fields = []): null|array|object
    {
        $this->limit(1);

        $row = $this->selectMany($fields);

        if ($row === []) {
            return null;
        }

        return $row[0];
    }

    /**
     * @var null|class-string<HydratedEntityType>
     */
    private ?string $hydrateEntityFqcn = null;

    /**
     * @param class-string<HydratedEntityType> $entityFqcn
     */
    public function hydrate(string $entityFqcn): self
    {
        if ($this->entityHydrator === null) {
            throw new \UnexpectedValueException('No hydrator set on the instance of the query builder');
        }

        $this->hydrateEntityFqcn = $entityFqcn;

        return $this;
    }

    private function buildSelectQueryString(): string
    {
        $fields = '*';

        if ($this->rawSelects !== []) {
            $fields = implode(',', $this->rawSelects);
        }

        if ($this->fields !== []) {
            $fields = implode(', ', array_map([$this, 'quoteField'], $this->fields));
        }
        // do not force to prefix the fields with the table name because it is not necessarily what is wanted

        $query = "SELECT $fields FROM $this->table ";
        $query .= $this->buildJoinQueryString();
        $query .= $this->buildWhereQueryString();
        $query .= $this->buildHavingQueryString();
        $query .= $this->buildGroupByQueryString();
        $query .= $this->buildOrderByQueryString();
        $query .= $this->limit;

        return $query . $this->offset;
    }

    private function buildExistsQueryString(): string
    {
        $query = "SELECT EXISTS(SELECT 1 FROM $this->table ";
        $query .= $this->buildJoinQueryString();
        $query .= $this->buildWhereQueryString();
        $query .= $this->buildHavingQueryString();
        $query .= $this->buildGroupByQueryString();
        $query .= $this->buildOrderByQueryString();
        $query .= $this->limit;
        $query .= $this->offset;

        return $query . ')';
    }

    public function count(): int
    {
        // calling selectMany() here since selectSingle() add a LIMIT clause
        return (int) (
            // @phpstan-ignore-next-line (Cannot access offset 'c' on array<string, bool|int|string>|object.)
            $this
                ->selectRaw('COUNT(*) as c')
                ->selectMany()[0]['c'] ?? 0
        );
    }

    public function exists(): bool
    {
        $this->action = self::ACTION_EXISTS;

        $statement = $this->pdo->prepare($this->toSql());
        $statement->execute(array_merge(
            $this->whereBindings,
            $this->havingBindings,
        ));

        return (bool) $statement->fetchColumn(); // the column '0' return either '0' or '1'
    }

    // --------------------------------------------------
    // insert

    /**
     * @var array<string> Names of the fields to insert, extracted from the first value
     */
    private array $fields = [];
    private int $insertedRowCount = 0;
    /**
     * @var array<string>
     */
    private array $upsertKeys = [];

    /**
     * @param array<mixed> $rows
     */
    public function insertMany(array $rows, string $actionType = self::ACTION_INSERT): bool
    {
        $this->action = $actionType;

        $this->fields = array_keys((array) $rows[0]);
        $this->insertedRowCount = \count($rows);

        // flatten all rows values, hopefully they are in the same order
        $rowValues = [];
        foreach ($rows as $row) {
            $rowValues[] = array_values((array) $row);  // this suppose here that all rows have the same key in the same order
        }

        return $this->pdo
            ->prepare($this->toSql())
            ->execute(array_merge(...$rowValues));
    }

    public function insertSingle(mixed $row): bool
    {
        return $this->insertMany([$row]);
    }

    public function lastInsertedId(?string $sequence = 'id'): int
    {
        return (int) $this->pdo->lastInsertId($sequence);
    }

    private function buildInsertQueryString(): string
    {
        if ($this->fields === []) {
            throw new \Exception('No field is set for INSERT action');
        }

        $fields = $this->fields;
        foreach ($fields as $i => $field) {
            $fields[$i] = $this->quoteField($field);
        }

        $rowPlaceholders = str_repeat('?, ', \count($fields));
        $row = '(' . substr($rowPlaceholders, 0, -2) . '), ';
        $rows = str_repeat($row, $this->insertedRowCount);

        return "INSERT INTO $this->table (" . implode(', ', $fields) . ')' .
            ' VALUES ' . substr($rows, 0, -2);
    }

    /**
     * @param array<mixed>  $rows
     * @param array<string> $keys
     */
    public function upsertMany(array $rows, array $keys): bool
    {
        $this->action = self::ACTION_UPSERT;

        $this->fields = array_keys((array) $rows[0]);
        $this->insertedRowCount = \count($rows);
        $this->upsertKeys = $keys;

        // flatten all rows values, hopefully they are in the same order
        $rowValues = [];
        foreach ($rows as $row) {
            $rowValues[] = array_values((array) $row);  // this suppose here that all rows have the same key in the same order
        }

        return $this->pdo
            ->prepare($this->toSql())
            ->execute(array_merge(...$rowValues));
    }

    /**
     * @param array<string> $keys
     */
    public function upsertSingle(mixed $row, array $keys): bool
    {
        return $this->upsertMany([$row], $keys);
    }

    private function buildUpsertQueryString(): string
    {
        if ($this->fields === []) {
            throw new \Exception('No field is set for UPSERT action');
        }

        $fields = $this->fields;
        foreach ($fields as $i => $field) {
            $fields[$i] = $this->quoteField($field);
        }

        $rowPlaceholders = str_repeat('?, ', \count($fields));
        $row = '(' . substr($rowPlaceholders, 0, -2) . '), ';
        $rows = str_repeat($row, $this->insertedRowCount);

        $sql = "INSERT INTO $this->table (" . implode(', ', $fields) . ') ';
        $sql .= 'VALUES ' . substr($rows, 0, -2);

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        \assert(\is_string($driver));
        $driver = strtolower($driver);
        switch ($driver) {
            case 'mysql':
                // https://dev.mysql.com/doc/refman/8.0/en/insert-on-duplicate.html
                $sql .= ' ON DUPLICATE KEY UPDATE ';

                $serverVersion = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                \assert(\is_string($serverVersion));
                $valuesDeprecated = version_compare($serverVersion, '8.0.20', '>=');

                $set = '';
                foreach ($fields as $field) {
                    $set .= "$field = ";

                    if ($valuesDeprecated) {
                        $set .= "new.$field, ";
                    } else {
                        $set .= "VALUES($field), ";
                    }
                }
                $sql .= substr($set, 0, -1);
                break;

            case 'pgsql':
            case 'sqlite':
                $keys = '';
                foreach ($this->upsertKeys as $upsertKey) {
                    $keys .= $this->quoteField($upsertKey) . ', ';
                }

                $set = '';
                foreach ($fields as $field) {
                    $set .= "$field = excluded.$field, ";
                }

                $sql .= ' ON CONFLICT (' . substr($keys, 0, -2) . ')';
                $sql .= ' DO UPDATE SET ' . substr($set, 0, -2);
                break;

            default:
                throw new \Exception("PDO driver '$driver' doesn't support UPSERT, or the it hasn't been implemented.");
        }

        return $sql;
    }

    // --------------------------------------------------
    // update

    /**
     * @param array<string, bool|int|string> $newData
     */
    public function update(array $newData): bool
    {
        $this->action = self::ACTION_UPDATE;

        $this->fields = array_keys($newData);

        return $this->pdo
            ->prepare($this->toSql())
            ->execute(array_merge(array_values($newData), $this->whereBindings));
    }

    private function buildUpdateQueryString(): string
    {
        if ($this->fields === []) {
            throw new \Exception('No field is set for UPDATE action');
        }

        $sql = "UPDATE $this->table SET ";

        foreach ($this->fields as $field) {
            $sql .= $this->quoteField($field) . ' = ?, ';
        }

        $sql = substr($sql, 0, -2) . ' ' . $this->buildWhereQueryString();

        return rtrim($sql);
    }

    // --------------------------------------------------
    // delete

    public function delete(): bool
    {
        $this->action = self::ACTION_DELETE;

        return $this->pdo
            ->prepare($this->toSql())
            ->execute($this->whereBindings);
    }

    // --------------------------------------------------
    // table

    private string $table = '';

    public function table(string $tableName): self
    {
        $this->table = $this->quoteField($tableName);

        return $this;
    }

    public function fromTable(string $tableName): self
    {
        return $this->table($tableName);
    }

    public function inTable(string $tableName): self
    {
        return $this->table($tableName);
    }

    // --------------------------------------------------
    // join

    /**
     * @var array<int, string> Table name by join id
     */
    private array $join = [];

    private int $lastJoinId = -1;

    /**
     * @var array<int, array<ConditionalClause|ConditionalGroup>> "on" clauses by join id
     */
    private array $onClauses = [];
    // unlike where and having
    // on is an array or conditional arrays

    private function buildJoinQueryString(): string
    {
        $str = '';
        foreach ($this->join as $id => $joinTable) {
            $str .= $joinTable . 'ON ';

            if (! isset($this->onClauses[$id]) || $this->onClauses[$id] === []) {
                throw new \Exception("Join statement without any ON clause: $joinTable");
            }

            $str .= $this->buildConditionalQueryString($this->onClauses[$id]) . ' ';
        }

        return $str;
    }

    public function join(string $tableName, string $alias = null, string $joinType = 'INNER'): self
    {
        $tableName = $this->quoteField($tableName);
        if ($alias !== null) {
            $tableName .= ' AS ' . $this->quoteField($alias);
        }

        $joinType = strtoupper($joinType);

        $allowedJoinTypes = ['INNER', 'LEFT', 'RIGHT', 'FULL', 'FULL OUTER'];
        if (! \in_array($joinType, $allowedJoinTypes, true)) {
            throw new \Exception("Unexpected join type '$joinType'.");
        }

        $joinType .= ' JOIN';

        $this->join[] = "$joinType $tableName ";
        ++$this->lastJoinId;

        return $this;
    }

    public function leftJoin(string $tableName, string $alias = null): self
    {
        return $this->join($tableName, $alias, 'LEFT');
    }

    public function rightJoin(string $tableName, string $alias = null): self
    {
        return $this->join($tableName, $alias, 'RIGHT');
    }

    public function fullJoin(string $tableName, string $alias = null): self
    {
        return $this->join($tableName, $alias, 'FULL');
    }

    public function on(string $field, string $operator, string $otherField, bool $or = false): self
    {
        $clause = new ConditionalClause();
        $clause->condition = $or ? 'OR' : 'AND';

        $operator = strtoupper($operator);
        $this->sanitizeComparisonOperator($operator);

        $clause->expression = $this->quoteField($field) . " $operator " . $this->quoteField($otherField);

        $this->onClauses[$this->lastJoinId] ??= [];
        $this->onClauses[$this->lastJoinId][] = $clause;

        return $this;
    }

    public function orOn(string $field, string $operator, string $otherField): self
    {
        return $this->on($field, $operator, $otherField, true);
    }

    // --------------------------------------------------
    // where

    /**
     * @var array<ConditionalClause|ConditionalGroup>
     */
    private array $whereClauses = [];

    /**
     * @var array<bool|int|string> All the values of the where clauses that match the placeholders
     */
    private array $whereBindings = [];

    private function buildWhereQueryString(): string
    {
        $where = $this->buildConditionalQueryString($this->whereClauses);
        if ($where !== '') {
            return "WHERE $where ";
        }

        return $where;
    }

    /**
     * @var array<string>
     */
    private array $comparisonOperators = [
        // copy from Laravel's Builder
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE', 'LIKE BINARY',
        '&', '|', '^', '<<', '>>', '&~',
        'RLIKE', 'NOT RLIKE', 'REGEXP', 'NOT REGEXP',
        '~', '~*', '!~', '!~*', '~~*', '!~~*',
        'SIMILAR TO', 'NOT SIMILAR TO',
    ];

    private function sanitizeComparisonOperator(string $operator): void
    {
        if (! \in_array($operator, $this->comparisonOperators, true)) {
            throw new \Exception("Comparison operator '$operator' is not allowed.");
        }
    }

    public function whereRaw(string $raw, bool $or = false): self
    {
        $clause = new ConditionalClause();
        $clause->condition = $or ? 'OR' : 'AND';
        $clause->expression = $raw;

        $this->whereClauses[] = $clause;

        return $this;
    }

    public function orWhereRaw(string $raw): self
    {
        return $this->whereRaw($raw, true);
    }

    public function whereGroup(callable $group, bool $or = false): self
    {
        $coutBefore = \count($this->whereClauses);
        $group($this);
        $coutAfter = \count($this->whereClauses);

        if ($coutAfter - $coutBefore <= 0) {
            return $this;
        }

        // if the closure as added where clauses ...
        $clause = new ConditionalGroup();
        $clause->condition = $or ? 'OR' : 'AND';

        // actually remove them from the $this->whereClauses property
        // to nest them in the ConditionalGroup clause.
        // And this all nicely works recursively :)
        $clause->clauses = array_splice($this->whereClauses, $coutBefore);

        $this->whereClauses[] = $clause;

        return $this;
    }

    public function orWhereGroup(callable $group): self
    {
        return $this->whereGroup($group, true);
    }

    public function where(string $field, string $operator, bool|int|string|Stringable $value, bool $or = false): self
    {
        if (\is_object($value)) {
            $value = (string) $value;
        }

        $this->whereBindings[] = $value;

        $operator = strtoupper($operator);
        $this->sanitizeComparisonOperator($operator);

        $sql = $this->quoteField($field) . " $operator ?";
        $this->whereRaw($sql, $or);

        return $this;
    }

    public function orWhere(string $field, string $sign, bool|int|string|Stringable $value): self
    {
        return $this->where($field, $sign, $value, true);
    }

    public function whereNull(string $field): self
    {
        return $this->whereRaw($this->quoteField($field) . ' IS NULL');
    }

    public function orWhereNull(string $field): self
    {
        return $this->whereRaw($this->quoteField($field) . ' IS NULL', true);
    }

    public function whereNotNull(string $field): self
    {
        return $this->whereRaw($this->quoteField($field) . ' IS NOT NULL');
    }

    public function orWhereNotNull(string $field): self
    {
        return $this->whereRaw($this->quoteField($field) . ' IS NOT NULL', true);
    }

    public function whereBetween(string $field, int|string $min, int|string $max, bool $or = false, bool $not = false): self
    {
        $this->whereBindings[] = $min;
        $this->whereBindings[] = $max;

        // "`field` BETWEEN ? AND ?"
        $sql = $this->quoteField($field) . ($not ? ' NOT' : '') . ' BETWEEN ? AND ?';

        return $this->whereRaw($sql, $or);
    }

    public function orWhereBetween(string $field, int|string $min, int|string $max): self
    {
        return $this->whereBetween($field, $min, $max, true);
    }

    public function whereNotBetween(string $field, int|string $min, int|string $max): self
    {
        return $this->whereBetween($field, $min, $max, false, true);
    }

    public function orWhereNotBetween(string $field, int|string $min, int|string $max): self
    {
        return $this->whereBetween($field, $min, $max, true, true);
    }

    /**
     * @param array<mixed> $values
     */
    public function whereIn(string $field, array $values, bool $or = false, bool $not = false): self
    {
        $this->whereBindings = array_merge($this->whereBindings, $values); // @phpstan-ignore-line (Property ... does not accept array.)

        $placeholders = substr(str_repeat('?, ', \count($values)), 0, -2);

        // "`field` IN (?,?)"
        $sql = $this->quoteField($field) . ($not ? ' NOT' : '') . " IN ($placeholders)";

        return $this->whereRaw($sql, $or);
    }

    /**
     * @param array<mixed> $values
     */
    public function orWhereIn(string $field, array $values): self
    {
        return $this->whereIn($field, $values, true);
    }

    /**
     * @param array<mixed> $values
     */
    public function whereNotIn(string $field, array $values): self
    {
        return $this->whereIn($field, $values, false, true);
    }

    /**
     * @param array<mixed> $values
     */
    public function orWhereNotIn(string $field, array $values): self
    {
        return $this->whereIn($field, $values, true, true);
    }

    // --------------------------------------------------
    // order by

    /**
     * @var array<string>
     */
    private array $orderBy = [];

    private function buildOrderByQueryString(): string
    {
        if ($this->orderBy === []) {
            return '';
        }

        return 'ORDER BY ' . implode(', ', $this->orderBy) . ' ';
    }

    public function orderBy(string $field, bool $ascending = true): self
    {
        $this->orderBy[] = $this->quoteField($field) . ($ascending ? ' ASC' : ' DESC');

        return $this;
    }

    public function smallestFirst(string $field): self
    {
        return $this->orderBy($field);
    }

    public function oldestFirst(string $field): self
    {
        return $this->orderBy($field);
    }

    public function biggestFirst(string $field): self
    {
        return $this->orderBy($field, false);
    }

    public function mostRecentFirst(string $field): self
    {
        return $this->orderBy($field, false);
    }

    // --------------------------------------------------
    // group by

    /**
     * @var array<string>
     */
    private array $groupBy = [];

    private function buildGroupByQueryString(): string
    {
        if ($this->groupBy === []) {
            return '';
        }

        return 'GROUP BY ' . implode(', ', $this->groupBy) . ' ';
    }

    /**
     * @param array<string>|string $fields
     */
    public function groupBy(array|string $fields): self
    {
        $this->groupBy[] = array_map([$this, 'quoteField'], (array) $fields); // @phpstan-ignore-line (Array (array<string>) does not accept array<string>.) ?

        return $this;
    }

    // --------------------------------------------------
    // having

    /**
     * @var array<ConditionalClause|ConditionalGroup>
     */
    private array $havingClauses = [];

    /**
     * @var array<bool|int|string> All the values of the having clauses that match the placeholders
     */
    private array $havingBindings = [];

    private function buildHavingQueryString(): string
    {
        $having = $this->buildConditionalQueryString($this->havingClauses);
        if ($having !== '') {
            return "HAVING $having ";
        }

        return $having;
    }

    public function havingRaw(string $raw, bool $or = false): self
    {
        $clause = new ConditionalClause();
        $clause->condition = $or ? 'OR' : 'AND';
        $clause->expression = $raw;

        $this->havingClauses[] = $clause;

        return $this;
    }

    public function orHavingRaw(string $raw): self
    {
        return $this->havingRaw($raw, true);
    }

    public function havingGroup(callable $group, bool $or = false): self
    {
        $coutBefore = \count($this->havingClauses);
        $group($this);
        $coutAfter = \count($this->havingClauses);

        if ($coutAfter - $coutBefore <= 0) {
            return $this;
        }

        // if the closure as added having clauses ...
        $clause = new ConditionalGroup();
        $clause->condition = $or ? 'OR' : 'AND';

        // actually remove them from the $this->havingClauses property
        // to nest them in the ConditionalGroup clause.
        // And this all nicely works recursively :)
        $clause->clauses = array_splice($this->havingClauses, $coutBefore);

        $this->havingClauses[] = $clause;

        return $this;
    }

    public function orHavingGroup(callable $group): self
    {
        return $this->havingGroup($group, true);
    }

    public function having(string $field, string $operator, bool|int|string|Stringable $value, bool $or = false): self
    {
        if (\is_object($value)) {
            $value = (string) $value;
        }

        $this->havingBindings[] = $value;

        $operator = strtoupper($operator);
        $this->sanitizeComparisonOperator($operator);

        $sql = $this->quoteField($field) . " $operator ?";
        $this->havingRaw($sql, $or);

        return $this;
    }

    public function orHaving(string $field, string $sign, bool|int|string|Stringable $value): self
    {
        return $this->having($field, $sign, $value, true);
    }

    // --------------------------------------------------
    // limit / offset

    private string $limit = '';

    public function limit(int $limit): self
    {
        $this->limit = "LIMIT $limit ";

        return $this;
    }

    private string $offset = '';

    public function offset(int $offset): self
    {
        $this->offset = "OFFSET $offset ";

        return $this;
    }

    public function paginate(int $page, int $perPage): self
    {
        $this->limit($perPage);
        $this->offset($perPage * ($page - 1));

        return $this;
    }

    // --------------------------------------------------
    // non-query building methods

    public function toSql(): string
    {
        if ($this->action === self::ACTION_EXISTS) {
            return $this->buildExistsQueryString();
        }

        if ($this->action === self::ACTION_SELECT) {
            return $this->buildSelectQueryString();
        }

        if ($this->action === self::ACTION_INSERT) {
            return $this->buildInsertQueryString();
        }

        if ($this->action === self::ACTION_UPSERT) {
            return $this->buildUpsertQueryString();
        }

        if ($this->action === self::ACTION_UPDATE) {
            return $this->buildUpdateQueryString();
        }

        if ($this->action === self::ACTION_DELETE) {
            return "DELETE FROM $this->table " .
                $this->buildWhereQueryString() .
                $this->buildOrderByQueryString() .
                $this->buildOrderByQueryString() .
                $this->limit;
        }

        throw new \Exception('QueryBuilder::toString() error: no action has been set');
    }

    /**
     * @param array<ConditionalClause|ConditionalGroup> $clauses
     */
    private function buildConditionalQueryString(array $clauses): string
    {
        if ($clauses === []) {
            return '';
        }

        $str = '';
        foreach ($clauses as $id => $clause) {
            if ($id > 0) {
                $str .= $clause->condition . ' ';
            }

            if ($clause instanceof ConditionalClause) {
                $str .= $clause->expression . ' ';

                continue;
            }

            $str .= '(' . $this->buildConditionalQueryString($clause->clauses) . ') ';
        }

        return rtrim($str);
    }

    /**
     * Escape field names by adding backticks (`) around them.
     */
    private function quoteField(string $field): string
    {
        $segments = explode('.', $field);

        $quotedField = '';
        foreach ($segments as $segment) {
            if (! str_starts_with($segment, '`') || ! str_ends_with($segment, '`')) {
                $quotedField .= "`$segment`.";
            }
        }

        return trim($quotedField, '.');
    }
}
