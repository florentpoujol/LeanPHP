<?php declare(strict_types=1);

namespace LeanPHP\EntityHydrator;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

final class EntityHydrator implements EntityHydratorInterface
{
    public function __construct(
        /**
         * @var array<class-string, array<string, string>> // First-level keys are entities FQCN, values are their data (key) to property (value) map
         */
        private array $dataToPropertyMapPerEntityFqcn = [],
    ) {
    }

    /**
     * @inheritDoc
     */
    public function hydrateOne(array $data, string $fqcn, array $dataToPropertyMap = []): object
    {
        $entity = new $fqcn;

        $reflectionProperties = $this->getReflectionProperties(array_keys($data), $fqcn, $dataToPropertyMap);
        foreach ($reflectionProperties as $dataKey => $reflectionProperty) {
            $reflectionProperty->setValue($entity, $data[$dataKey]);
        }

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function hydrateMany(array $data, string $fqcn, array $dataToPropertyMap = []): array
    {
        $reflectionProperties = $this->getReflectionProperties(array_keys($data[0]), $fqcn, $dataToPropertyMap);

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
     * @param array<string, string> $dataToPropertyMap Keys are data keys, values are the property names
     *
     * @return array<string, ReflectionProperty>
     */
    private function getReflectionProperties(array $dataKeys, string $fqcn, array $dataToPropertyMap): array
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

            if ($dataToPropertyMap === []) {
                if (!isset($this->dataToPropertyMapPerEntityFqcn[$fqcn])) {
                    $this->discoverDatToPropertyMapFromAttribute($fqcn);
                }

                $dataToPropertyMap = $this->dataToPropertyMapPerEntityFqcn[$fqcn];
            }

            if (isset($dataToPropertyMap[$dataKey])) {
                $propertyName = $dataToPropertyMap[$dataKey];
                $reflectionProperties[$dataKey] = $reflectionClass->getProperty($propertyName);
            }
        }

        return $reflectionProperties;
    }

    /**
     * @param class-string $fqcn
     */
    private function discoverDatToPropertyMapFromAttribute(string $fqcn): void
    {
        $reflAttributes = (new ReflectionClass($fqcn))->getAttributes(DataToPropertyMap::class, ReflectionAttribute::IS_INSTANCEOF);

        $this->dataToPropertyMapPerEntityFqcn[$fqcn] = $reflAttributes[0]->getArguments()[0] ?? [];
    }
}