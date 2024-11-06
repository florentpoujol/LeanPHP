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

## DottedKeyValueStore

The `DottedKeyValueStore` collection is basically a store for nested associative arrays where you can manipulate nested value with dotted keys.

Ie: 
```php
$store = new DottedKeyValueStore([
    'root' => [
        'numericStringKey' => '5',
        'nullKey' => 'will be set null',
        'willBeUnset' => true,
    ],
]);

$store->set('root.numericStringKey', '10');
$store->set('root.nullKey', null);

$store->unset('unknown');
$store->unset('root.willBeUnset');

$store->has('unknown'); // false
$store->has('root'); // true
$store->has('root.unknown'); // false
$store->has('root.nullKey'); // true
$store->has('root.willBeUnset'); // false

// get(string $key, mixed $default = null): mixed
$store->get('unknown'); // null
$store->get('unknown', 'default'); // 'default'
$store->get('root'); // array{numericStringKey: 10, nullKey: null}
$store->get('root.numericStringKey'); // '10'
$store->get('root.nullKey'); // null
$store->get('root.nullKey', 'default'); // null

// There is also a typed version of the get() method for string, int, float, bool and array.
// The $default argument is optional and nullable.
// When the value isn't set and no default is provided, it will throw a NoValueFoundException

// ie with the int version
$store->getInt('unknown'); // NoValueFoundException "No value found for key 'unknown', and no default provided."
$store->getInt('unknown', null); // null 
$store->getInt('unknown', 42); // 42

$store->getInt('numericStringKey'); // 10
$store->getInt('numericStringKey', 42); // 10
```