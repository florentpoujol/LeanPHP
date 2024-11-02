<?php

declare(strict_types=1);

namespace LeanPHP\Container;

/**
 * @phpstan-import-type ContainerFactory from Container
 */
final readonly class Binding
{
    public function __construct(
        /**
         * @var string|class-string An alias, interface FQCN or contrete implementation FQCN
         */
        public string $serviceName,
        /**
         * @var ContainerFactory|string The factory callable, another alias, an interface FQCN or an concrete implementation alias
         */
        public mixed $factoryOrConcreteOrAlias,
        public bool $isSingleton = true,
    ) {
    }
}

/*
service name is concrete
factory is callable

service name is interface
factory is callable

service name is alias
factory is callable


service name is interface
factory is concrete

service name is alias
factory is concrete

service name is alias
factory is another alias (interface or alias)
 */