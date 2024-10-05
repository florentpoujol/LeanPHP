<?php declare(strict_types=1);

namespace LeanPHP\Database;

use LeanPHP\EntityHydrator\EntityHydrator;
use LeanPHP\EntityHydrator\EntityHydratorInterface;

abstract class AbstractModel
{
    public static function getDatabaseTable(): string
    {
        // pluralize the children base name
        $lastSlashPos = strrpos(static::class, '\\');
        \assert(\is_int($lastSlashPos));

        return strtolower(substr(static::class, $lastSlashPos + 1)) . 's'; // "Entity\User" > "users"
    }

    /**
     * @param array<string, null|scalar> $row
     */
    public static function fromDatabaseRow(array $row): static
    {
        return (static::getHydrator())->hydrateOne($row, static::class);
    }

    protected static function getHydrator(): EntityHydratorInterface
    {
        return new EntityHydrator();
    }

    /**
     * @return array<string, null|scalar>
     */
    public function toDatabaseRow(): array
    {
        // TODO move that logic into a Marshaller/Serializer and take into account the data to property map

        $row = (array) $this; // this doesn't convert non scalar values

        foreach ($row as $key => $value) {
            if (\is_scalar($value)) {
                continue;
            }

            if (\is_array($value)) {
                $row[$key] = json_encode($value); // what else is there to do ?

                continue;
            }

            // $value is an object instance
            $row[$key] = match (true) {
                $value instanceof self => $value->toDatabaseRow(),
                $value instanceof \Stringable => (string) $value,
                $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \JsonSerializable => $value->jsonSerialize(), // does this even make sens ?
                default => throw new \UnexpectedValueException("Can't transform object of type '" . get_debug_type($value) . "' to scalar for property/key '" . $this::class . "::$$key'."),
            };
        }

        return $row;
    }
}