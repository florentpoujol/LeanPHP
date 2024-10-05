# Entity Hydrator

LeanPHP provide a simple hydrator that can fill an entity's properties with values coming from an array with data.

The entity constructor isn't called, all properties are filled via reflection, regarding of their visibility.

Type declaration of the properties are taken into account, and if the property names are different from the keys in your data, you can declare a map. 

```php
final readonly class MyEntity
{
    public int $id;
    public DateTimeImmutable $createdAt
}

$data = [
    'id' => '1',
    'created_at' => '2024-10-05 11:15:30'
]

$entity = (new Hydrator())->hydrateOne($data, MyEntity::class);
$entities = (new Hydrator())->hydrateMany([$data, $data], MyEntity::class);
```
## Set the map between data keys and property names

You have three ways to set this:
- via the `array<class-string, array<string, string>> $dataToPropertyMapPerEntityFqcn = []` constructor argument of the hydrator instance
- via the `DataToPropertyMap` attribute on the entity
- via the optional third `array<string string> $dataToPropertyMap = []` argument on the `hydrate*` methods of the hydrator. In that case the map that may be set in the other two ways are ignored.

Examples:
```php
// via attribute on entities
#[DataToPropertyMap(['created_at' => 'createdAt'])]
final readonly class MyEntity
{
    public int $id;
    public DateTimeImmutable $createdAt
}

// via the hydrator  constructor
$hydrator = new EntityHydrator([
    MyEntity::class => ['created_at' => 'createdAt'],
]);

// when hydrating the entities
$data = [
    'id' => '1',
    'created_at' => '2024-10-05 11:15:30'
]

$entity = $hydrator->hydrateOne($data, MyEntity::class, ['created_at' => 'createdAt']);
```