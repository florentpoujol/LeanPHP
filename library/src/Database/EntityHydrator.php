<?php declare(strict_types=1);

namespace LeanPHP\Database;

use ReflectionClass;
use ReflectionProperty;

final readonly class EntityHydrator implements EntityHydratorInterface
{
    /**
     * @inheritDoc
     */
    public function hydrateOne(array $data, string $fqcn): object
    {
        $entity = new $fqcn;

        $reflectionProperties = $this->getReflectionProperties(array_keys($data), $fqcn);
        foreach ($reflectionProperties as $dataKey => $reflectionProperty) {
            $reflectionProperty->setValue($entity, $data[$dataKey]);
        }

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function hydrateMany(array $data, string $fqcn): array
    {
        $reflectionProperties = $this->getReflectionProperties(array_keys($data[0]), $fqcn);

        $entities = [];
        $cloneSource = new $fqcn;

        foreach ($data as $row) {
            $rowEntity = clone $cloneSource;
            $entities[] = $rowEntity;

            foreach ($reflectionProperties as $dataKey => $reflectionProperty) {
                $reflectionProperty->setValue($rowEntity, $row[$dataKey]);
            }
        }

        return $entities;
    }

    /**
     * @param array<string> $dataKeys
     * @param class-string $fqcn
     *
     * @return array<string, ReflectionProperty>
     */
    private function getReflectionProperties(array $dataKeys, string $fqcn): array
    {
        $reflectionClass = new ReflectionClass($fqcn);

        /** @var array<string, ReflectionProperty> $reflectionProperties */
        $reflectionProperties = [];
        foreach ($dataKeys as $dataKey) {
            $propertyName = $dataKey;

            if ($reflectionClass->hasProperty($propertyName)) {
                $reflectionProperties[$dataKey] = $reflectionClass->getProperty($propertyName);

                continue;
            }

            // try again by transforming the property name from camel to snake case
            if (str_contains($dataKey, '_')) {
                $propertyName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $dataKey))));

                if ($reflectionClass->hasProperty($propertyName)) {
                    $reflectionProperties[$dataKey] = $reflectionClass->getProperty($propertyName);
                }
            }
        }

        return $reflectionProperties;
    }
}