<?php

declare(strict_types=1);

namespace LeanPHP;

abstract class AbstractProxy
{
    abstract protected function getService(): object;

    /**
     * @param array<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->getService()->{$name}(...$arguments);
    }

    public function __get(string $name): mixed
    {
        return $this->getService()->{$name};
    }

    public function __set(string $name, mixed $value): void
    {
        $this->getService()->{$name} = $value;
    }
}