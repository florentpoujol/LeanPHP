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
        $row = (array) $this; // this doesn't convert non scalar values

        foreach ($row as $key => $value) {
            if (!\is_object($value)) {
                continue;
            }

            if ($value instanceof self) {
                $row[$key] = $value->toDatabaseRow();

                continue;
            }

            if ($value instanceof \Stringable) {
                $row[$key] = (string) $value;

                continue;
            }

            if ($value instanceof \JsonSerializable) {
                $row[$key] = $value->jsonSerialize();

                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $row[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $row;
    }
}