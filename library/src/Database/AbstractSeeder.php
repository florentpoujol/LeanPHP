<?php declare(strict_types=1);

namespace LeanPHP\Database;

use LeanPHP\Container;

abstract class AbstractSeeder
{
    protected \PDO $pdo;

    public function setPdo(\PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    protected Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * @template ModelFactory of AbstractModelFactory
     *
     * @param class-string<ModelFactory> $factoryFqcn
     *
     * @return ModelFactory
     */
    protected function getFactory(string $factoryFqcn): object
    {
        return $this->container->get($factoryFqcn);
    }

    abstract public function run(): void;
}