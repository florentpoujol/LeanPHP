# Identifiers

The identifier component allows to easily generate (universally) unique identifier values in an object-oriented way.

## Available identifiers

### UUID v1

You can create a standard UUID v1 like so:
```php
$uuid = UUIDv1::make();

$uuid->getUuid(); // ie: 7db6a770-b383-11b2-2e8d-024245e26f1b
$uuid->getHex(); // ie: 7db6a770b38311b22e8d024245e26f1b
```

You can also create a UUID objects from a pre-existing string instead of generating a new one:
```php
$uuid = UUIDv1::fromString('7db6a770-b383-11b2-2e8d-024245e26f1b');
$uuid = UUIDv1::fromString('7db6a770b38311b22e8d024245e26f1b');
```

The "node" is the last 12 characters (last 6 bytes) and should actually be the MAC address of the computer where the UUID is generated, so that it is always the same and different from every other UUIDv1 generated from other computers.  

Finding the MAC address requires some utilities to be installed on the computer:
- `ipconfig` on Windows
- `ifconfig` on Darwin
- `netstat` on all others OS

On Linux-based systems you should ake sure the package "net-tools", which contain both ifconfig and netstat is installed.

But you can also manually set a node via the `UUIDv1::setNode(?string $node)` method, or set the node from a callable to be set via the `UUIDv1::setNodeProvider(?callable $provider)`.  
The node must be provided as a 12 character hexadecimal string.

If the MAC address can't be resolved and no node provider is given, the node will be generated randomly the first time one is needed. Further UUID v1 generation wil reuse the same node. 

Also notes on the implementation :
- timestamp is only precise up to the microsecond and not up to the tens of a micro second 
- the clock sequence is random

### UUID v4

You can create a standard UUID v4 like so:
```php
$uuid = UUIDv4::make();

$uuid->getUuid(); // ie: 9ab8188a-2384-46c2-9b9a-aed9aefead41
$uuid->getHex(); // ie: 9ab8188a238446c29b9aaed9aefead41
```

You can also create a UUID objects from a pre-existing string instead of generating a new one:
```php
$uuid = UUIDv4::fromString('9ab8188a-2384-46c2-9b9a-aed9aefead41');
$uuid = UUIDv4::fromString('9ab8188a238446c29b9aaed9aefead41');
```

### TimeBased8

The TimeBased8 identifier generate an always positive, incrementing integer, composed of the micro-timestamp on the 7 first bytes and of 1 trailing random byte.    

```php
$uuid = TimeBased8::make();

$uuid->getUuid(); // UnexpectedValueException because the value isn't 16 bytes
$uuid->getHex(); // ie: 05d8e8e55fb78ef2
$uuid->getInteger(); // ie: 421342637010161394
```

You can also create an objects from a number:
```php
$uuid = TimeBased8::fromHex('05d8e8e55fb78ef2');
$uuid = TimeBased8::fromInteger(421342637010161394);
```

As of February 2022 the value is still small enough to be stored on a signed 8 bytes integer.

Unlike UUID v1, since the timestamp's bits  are in the"regular/hi mid low" order (the bits that changes the most are toward the end), they are indexing friendly ([without shenanigans](https://stitcher.io/blog/optimised-uuids-in-mysql#it-becomes-even-better!)) and so can be good candidate for database primary keys.

### TimeBased16

Same as the TimeBase8, but that trails with 9 random bytes instead of 1, for more uniqueness.

```php
$uuid = TimeBased16::make();

$uuid->getUuid(); // ie: 05d8e8ef-ae53-daa0-dde8-fbce18cc5639
$uuid->getHex(); // ie: 05d8e8efae53daa0dde8fbce18cc5639
```

## Create your own

To create your own identifier class:
- implements the `IdentifierInterface` interface
- or extends the abstract `Identifier` class and implement the `generate(): string` method that must return the identifier as a binary string.
