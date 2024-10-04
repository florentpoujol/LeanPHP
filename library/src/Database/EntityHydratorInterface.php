<?php declare(strict_types=1);

namespace LeanPHP\Database;

interface EntityHydratorInterface
{
    /**
     * @template T of object
     *
     * @param array<string, mixed> $data
     * @param class-string<T> $fqcn
     *
     * @return T
     */
    public function hydrateOne(array $data, string $fqcn): object;

    /**
     * @template T of object
     *
     * @param array<array<string, mixed>> $data
     * @param class-string<T> $fqcn
     *
     * @return array<T>
     */
    public function hydrateMany(array $data, string $fqcn): array;
}