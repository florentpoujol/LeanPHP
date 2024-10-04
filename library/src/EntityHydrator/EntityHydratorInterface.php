<?php declare(strict_types=1);

namespace LeanPHP\EntityHydrator;

interface EntityHydratorInterface
{
    /**
     * @template T of object
     *
     * @param array<string, mixed> $data
     * @param class-string<T> $fqcn
     * @param array<string, string> $dataToPropertyMap Keys are data keys, values are the property names
     *
     * @return T
     */
    public function hydrateOne(array $data, string $fqcn, array $dataToPropertyMap = []): object;

    /**
     * @template T of object
     *
     * @param array<array<string, mixed>> $data
     * @param class-string<T> $fqcn
     * @param array<string, string> $dataToPropertyMap Keys are data keys, values are the property names
     *
     * @return array<T>
     */
    public function hydrateMany(array $data, string $fqcn, array $dataToPropertyMap = []): array;
}