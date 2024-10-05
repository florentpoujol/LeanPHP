<?php declare(strict_types=1);

namespace LeanPHP\EntityHydrator;

use LeanPHP\Container;
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
        return $this->hydrateMany([$data], $fqcn, $dataToPropertyMap)[0];
    }

    /**
     * @inheritDoc
     */
    public function hydrateMany(array $rows, string $fqcn, array $dataToPropertyMap = []): array
    {
        $reflectionProperties = $this->getReflectionProperties(array_keys($rows[0]), $fqcn, $dataToPropertyMap);

        $entities = [];
        $reflectionClass = new ReflectionClass($fqcn);

        foreach ($rows as $row) {
            $entity = $reflectionClass->newInstanceWithoutConstructor();
            $entities[] = $entity;

            foreach ($reflectionProperties as $dataKey => $reflectionProperty) {
                $this->setPropertyValue($entity, $reflectionProperty, $row[$dataKey]);
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

    private function setPropertyValue(object $entity, ReflectionProperty $reflectionProperty, mixed $value): void
    {
        $reflectionType = $reflectionProperty->getType();
        if ($reflectionType === null) {
            $reflectionProperty->setValue($entity, $value);

            return;
        }

        if ($value === null && $reflectionType->allowsNull()) {
            $reflectionProperty->setValue($entity, null);

            return;
        }

        if ($reflectionType instanceof \ReflectionUnionType || $reflectionType instanceof \ReflectionIntersectionType) {
            $propertyName = $reflectionProperty->getName();
            $className = $entity::class;
            throw new \Exception("The hydrator do not support intersection or union types, for property '$className::$$propertyName'.");
        }

        /** @var \ReflectionNamedType $reflectionType */
        $propertyType = $reflectionType->getName();

        if (
            get_debug_type($value) === $propertyType
            || $reflectionType->isBuiltin() // in this case we hope the value can be cast to the correct type, otherwise throws a type error
        ) {
            $reflectionProperty->setValue($entity, $value);

            return;
        }

        // we are here, the declared type is not built-in, so it's a class/interface
        /** @var class-string $propertyType */

        if (!interface_exists($propertyType)) {
            $reflectionProperty->setValue($entity, new $propertyType($value));

            return;
        }

        // else this is an interface so we have to resolve the binding from the container

        $container = Container::getInstance();
        $binding = $container->getBinding($propertyType);

        if ($binding === null) {
            $propertyName = $reflectionProperty->getName();
            $className = $entity::class;
            throw new \Exception("Can't hydrate property '$className::$$propertyName' because its type is the interface '$propertyType' and no concrete implementation can be resolved from the container.");
        }

        if (\is_string($binding)) {
            $reflectionProperty->setValue($entity, new $binding($value));
        } else {
            // $binding is the callable factory
            $reflectionProperty->setValue($entity, $binding($container, $value)); // @phpstan-ignore-line (Callable callable(): object invoked with 2 parameters, 0 required.)
        }
    }
}