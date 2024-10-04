# Query Builder

The query builder helps you create SQL queries in a fluent way, and execute them through PDO.

## Action methods

The methods that actually trigger the SQL request are the ones named after the action SELECT, INSERT, UPDATE, INERT OR UPDATE and DELETE.
Each (except delete) have 2 variants: single and many.

### Select

```php
$data = $qb->fromTable('table')
    ->selectSingle(['field1', 'field2']);
/*
[
    [
        'field1' => 'value',
        'field2' => 'value2',    
    ],
    [
        'field1' => 'value',
        'field2' => 'value2',    
    ],    
]
*/

$entries = $qb->fromTable('table')
    ->where('field1', '=', 'value')
    ->limit(10)
    ->selectMany(['field1', 'field2']);
/*
[
    [
        'field1' => 'value',
        'field2' => 'value2',    
    ],
    [
        'field1' => 'value',
        'field2' => 'otherValue2',    
    ],    
]
*/
```

**Hydrating entities**

When selecting, returned rows are array by default, but you can make the query builder return hydrated entities objects if you pass the entity fully qualified class name to the `hydrate(string $entityFqcn): self` method before calling `selectSingle()` or `selectMany()` methods.

Properties of the entity that match the snakeCase version of the selected field will be hydrated directly, even if protected/private, without using any setter.

### Exists

```php
$exists = $qb->fromTable('table')
    ->where('field1', '=', 'value')
    ->exists(); // true or false
```

### Insert
```php
$data = [
    'field' => 'value',
    'field2' => 'value2',    
];
$qb->inTable('table')->insertSingle($data);

$data = [
    [
        'field' => 'value',
        'field2' => 'value2',    
    ],
    [
        'field' => 'value',
        'field2' => 'other value2',    
    ],    
];
$qb->inTable('table')->insertMany($data);
```

### Update
```php
$data = [
    'field' => 'value',
    'field2' => 'new value2',    
];
$qb->inTable('table')->where('field', '=', 'value')->updateSingle($data);

$data = [
    [
        'field2' => 'new value2',    
    ],
    [
        'field2' => 'new other value2',    
    ],    
];
$qb->inTable('table')->where('field', '=', 1)->updateMany($data);
```

### Upsert

An upsert is an "insert or update", the database will insert the rows if they do not exists, or update the rows if they don't.

Determining if the rows exist is done on one of the field that has a UNIQUE index.

This is supported only for MySQL, postgreSQL or SQLite.

```php
$data = [
    'field' => 'value',
    'field2' => 'new value2',    
];
$qb->inTable('table')->upsertSingle($data, ['field']);

$data = [
    [
        'field' => 'value',
        'field2' => 'new value2',    
    ],
    [
        'field' => 'value2',
        'field2' => 'new other value2',    
    ],    
];
$qb->inTable('table')->upsertMany($data, ['field']);
```

The second argument of the `upsert*()` method is the list of the fields on which the comparison is done.

### Delete
```php
$qb->inTable('table')->where('field', '=', 'value')->delete();
```

### Table

Set the query's main table.

```php
$qb->fromTable('the_table');
$qb->fromTable('the_table AS alias');
// or
$qb->inTable('the_table');
```

### Where

```php
$qb->where('field', '=', 'value');
$qb->where('field', '>=', 'value');
$qb->where('field', 'LIKE', '%John%');

$data = [
    'field' => 'value',
    'otherField' => 'otherValue',
];
$qb->whereFields($data);
// is the same as
$qb
    ->where('field', '=', 'value')
    ->where('otherField', '=', 'otherValue');

$qb->whereNull('nullableField');
$qb->whereNotNull('nullableField');

$qb->whereBetween('numberOrDateField', $min, $max);
$qb->whereNotBetween('numberOrDateField', $min, $max);

$qb->whereIn('field', $values);
$qb->whereNotIn('field', $values);
```

All these methods also have an equivalent prefixed with 'or' (`orWhere`, `orWhereNotNull`, `orWhereIn`, ...).

```php
$qb
    ->where('field = value')
    ->orWhereNull('$otherField')
    ->whereIn('anotherField', [1, 2, 3]);

// this would generate the following query string
// 'WHERE field = value OR otherField IS NULL AND anotherField IN (1, 2, 3)'
```

__Nested conditions__

To nest conditions, you must pass a callable to the `whereGroup(callable $group)`/`orWhereGroup(callable $group)`method.  
The callable receive the query builder instance as only parameter.

```php
$qb
    ->where('field', '=', 'value')
    ->orWhereGroup(function (QueryBuilder $qb): void {
        $qb
            ->where('field', '=', 'value')
            ->where('field2', '=', 'value2');
    })
    ->orWhereNotIn('anotherField', [1, 2, 3]);

// this would generate the following query string
// 'WHERE field = value OR (field = value AND field2 = value) OR anotherField NOT IN (1, 2, 3)'
```

### Join

Use the `join()` and `on()` methods. `on()` works just like `where()`.

```php
$qb
    ->join('the_table AS alias')
    ->on('field', '=', 'value');
```

For more join types, you can use `leftJoin()` , `rightJoin()`, `fullJoin()` or pass the join type as the second parameter of `join()`.

```php
$qb->join('the_table', 'LEFT');
// is the same as
$qb->leftJoin('the_table');
```

You can add several join clauses. The conditions specified via the `on()` method are linked to the join clause created by the last call to the `join()` method.

```php
$qb
    ->join('the_table')->on('field', '>=', 'value')
    ->join('the_other_table')->on('field', '>=', 'value');
```

### Other select triage methods

__Group By__

```php
$qb->groupBy('field')->groupBy('field2');
// or 
$qb->groupBy(['field', 'field2']);
```

__Having__

Works just like `where()`.

```php
$qb
    ->having('field', '=', 'value')
    ->orHaving('field', '<=', 'value')
    ->orHavingGroup(function (QueryBuilder $qb): void {
        $qb->having('field', '=', 'value');
        $qb->having('field',  'LIKE', '%value%');
    });
// query string:
// HAVING field = value OR field = value AND field LIKE '%value%' OR (field = value AND field LIKE '%value%')
```

__Order By__

```php
$qb->orderBy('field')->orderBy('field2', 'desc');
// ORDER BY field ASC, field2 DESC 

// there is also 4 conveniences methods
$qb->smallestFirst('field');
$qb->biggestFirst('field');

$qb->oldestFirst('field');
$qb->mostRecentFirst('field');
```

__Limit and Offset__

```php
$qb->limit(10)->offset(5);
```
