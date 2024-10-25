# Generic and type-safe collections

## List<T>

Lean provide a generic `List` collection with the proper PHPDocs setup so that it appears generic, and a way to optionnaly enforce the type of the values when inserting them.

The collection has most `array_*` methods onto it.

You can build a new List by providing the initial list of values in the constructor.

```php
/** @var List<int> $list */
$list = new List([1, 2, 3]);

$value = $list->pop(); // 3
// $value is understood as being of type 'int'
```

By default, types aren't checked, so this would be valid:
```php
$list = new List([1, '2', 3.0]);
$list->push(new stdClass);
```

You can set the expected type of the values as the constructor's `expectedType` argument, which is one of the `CollectionValueType` enum.
```php
$list = new List(
    data: [1, 2, 3],
    valueType: CollectionValueType::int
);

$list->push('4'); // type error
```

The argument's default value is `CollectionValueType::dontCheck`.  
Instead of a specific type, you can set it to `CollectionValueType::fromFirstValue` so that the type is set from the first inserted value's type.

Types are only checked when values are inserted in the collection, from the constructor or any methods that change the collection.

Without type checking, the List collection is not more than an object-oriented array, since array already can be declared generic via PHPDocs.

## Map<KeyT, ValueT>

Similarly, the `Map` collection is an object-oriented, generic and type-safe associative array.

Types of the key and values can be specified via the keyType and valueType arguments of the constrctor, and can also be set from the first KV pair

## All types

```php
CollectionValueType::dontCheck
CollectionValueType::fromFirstValue

// scalars
CollectionValueType::int
CollectionValueType::float
CollectionValueType::bool
CollectionValueType::object
CollectionValueType::array

// strings
CollectionValueType::string
CollectionValueType::nonEmptyString
CollectionValueType::classString // the values must be the FQCN of an existing class or interface
```