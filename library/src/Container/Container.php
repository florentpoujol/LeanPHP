<?php declare(strict_types=1);

namespace LeanPHP\Container;

use DateTimeImmutable;
use DateTimeInterface;
use LeanPHP\EntityHydrator\EntityHydrator;
use LeanPHP\EntityHydrator\EntityHydratorInterface;
use LeanPHP\Hasher\BuiltInPasswordHasher;
use LeanPHP\Hasher\HasherInterface;
use LeanPHP\Validation\Validator;
use LeanPHP\Validation\ValidatorInterface;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * @phpstan-type ContainerFactory callable(self $container, array<string, mixed> $extraArguments): object
 */
final class Container
{
    /**
     * @var array<string|class-string, Binding>
     */
    private array $bindings = [];

    /**
     * Values cached by getInstance() or passed to setInstance().
     *
     * @var array<string|class-string, object>
     */
    private array $instances = [];

    private function __construct()
    {
        $this->instances[self::class] = $this;

        $defaultBindings = [
            new Binding(ResponseInterface::class, Response::class, false),
            new Binding(DateTimeInterface::class, DateTimeImmutable::class, false),
            new Binding(ValidatorInterface::class, Validator::class, false),

            new Binding(RequestInterface::class, Request::class), // client request
            new Binding(HasherInterface::class, BuiltInPasswordHasher::class),
            new Binding(EntityHydratorInterface::class, EntityHydrator::class),
        ];

        /** @var Binding $binding */
        foreach ($defaultBindings as $binding) {
            $this->bindings[$binding->serviceName] = $binding;
        }
    }

    private static ?self $self = null;

    public static function get(): self
    {
        return self::$self ??= new self();
    }

    public static function new(): self
    {
        return self::$self = new self();
    }

    /**
     * @param string|class-string $aliasName The interface FQCN or alias
     * @param string|class-string $aliasedName The concrete or interface FQCN, or alias
     */
    public function alias(string $aliasName, string $aliasedName, bool $isSingleton = true): void
    {
        if (class_exists($aliasedName) && !interface_exists($aliasName)) {
            throw new ContainerException("The parameter \$aliasName '$aliasName' is an actual class. It should only be an interface FQCN or an arbitrary alias. Maybe you inverted both parameter ?");
        }

        $this->bindings[$aliasName] = new Binding($aliasName, $aliasedName, isSingleton: $isSingleton);
    }

    /**
     * @param class-string $serviceName The concrete or interface FQCN, or alias
     * @param ContainerFactory $factory
     */
    public function setFactory(string $serviceName, callable $factory, bool $isSingleton = true): void
    {
        $this->bindings[$serviceName] = new Binding($serviceName, $factory, isSingleton: $isSingleton);
    }

    /**
     * @param string|class-string $alias An arbitrary alias
     * @param bool $replace When false, an exception will be thrown if either the class or alias already exists
     */
    public function setInstance(object $instance, null|string $alias = null, bool $replace = false): void
    {
        $fqcn = $instance::class;
        if (isset($this->instances[$instance::class]) && !$replace) {
            $withAlias = $alias !== null ? " with alias $alias" : '';
            throw new ContainerException("Can not set an instance of type $fqcn$withAlias in the container, because an instance already exists.");
        }

        if ($alias !== null && isset($this->instances[$alias]) && !$replace) {
            $existingType = $this->instances[$alias]::class;
            throw new ContainerException(
                "Can not set an instance of type $fqcn with alias $alias in the container," .
                " because the alias already exists with an instance of type $existingType.",
            );
        }

        if ($alias !== null) {
            if (str_contains($alias, '\\') && !($instance instanceof $alias)) {
                throw new ContainerException("Alias '$alias' for instance of type $fqcn seems to be a class name, but the instance doesn't implement or extend it");
            }

            $this->instances[$alias] = $instance;
        }
        $this->instances[$instance::class] = $instance;
    }

    /**
     * @param string|class-string $serviceName Concrete or interface Fqcn, or alias
     */
    public function hasService(string $serviceName): bool
    {
        return
               isset($this->instances[$serviceName])
            || isset($this->bindings[$serviceName]);
    }

    /**
     * @template Service of object
     *
     * @param class-string<Service> $serviceName Concrete or interface Fqcn, or alias
     * @param array<string, mixed> $extraArguments
     *
     * @return Service
     *
     * @throws ContainerException When the service can't be resolved
     */
    // Note Florent: technically the $serviceName can receive a regular string, but if the argument is typed as string|class-string, PHPStan throws a tantrum and doesn't recognize the Service as being used in an argument
    public function getInstance(string $serviceName, array $extraArguments = []): object
    {
        if (isset($this->instances[$serviceName])) {
            /** @var Service $object */
            $object = $this->instances[$serviceName];

            return $object;
        }

        $object = $this->makeInstance($serviceName, $extraArguments);
        if ($object === null) {
            throw new ContainerException("Service '$serviceName' couldn't be resolved", 1);
        }

        $binding = $this->bindings[$serviceName] ?? null;
        if ($binding?->isSingleton === false) {
            $this->instances[$serviceName] = $object;
            $this->instances[$object::class] = $object;
        }

        return $object;
    }

    /**
     * Returns a new instance of object or call again a callable.
     *
     * @template Service of object
     *
     * @param class-string<Service> $serviceName Concrete or interface Fqcn, or alias
     * @param array<string, mixed> $extraArguments
     *
     * @return null|Service
     */
    // Note Florent: technically the $serviceName can receive a regular string, but if the argument is typed as string|class-string, PHPStan throws a tantrum and doesn't recognize the Service as being used in an argument
    public function makeInstance(string $serviceName, array $extraArguments = []): ?object
    {
        $binding = $this->resolveBinding($serviceName);

        if ($binding === null) {
            if (class_exists($serviceName)) {
                return $this->createObject($serviceName, $extraArguments);
            }

            // throw new ContainerException("Factory or concrete class FQCN could be found for interface or alias '$serviceName'.");
            return null;
        }

        $this->bindings[$serviceName] = $binding;

        /** @var ContainerFactory|class-string<Service> $concrete */
        $concrete = $binding->factoryOrConcreteOrAlias;
        if (\is_callable($concrete)) {
            return $concrete($this, $extraArguments);
        }

        if (\is_string($concrete) && class_exists($concrete)) {
            return $this->createObject($concrete, $extraArguments);
            // /** @var Service $object */
            // $object = $this->createObject($concrete, $extraArguments);
            // return $object;
        }

        // throw new ContainerException("Service '$serviceName' resolve to a value '$concrete' that is neither another known service nor a class name.");
        return null;
    }

    /**
     * @param string|class-string $serviceName Concrete or interface Fqcn, or alias
     */
    public function resolveBinding(string $serviceName): ?Binding
    {
        $binding = null;

        while (isset($this->bindings[$serviceName])) {
            $binding = $this->bindings[$serviceName];

            if (\is_string($binding->factoryOrConcreteOrAlias) && !\is_callable($binding->factoryOrConcreteOrAlias)) {
                $serviceName = $binding->factoryOrConcreteOrAlias;
            } else {
                break;
            }
        }

        return $binding;
    }

    /**
     * @template Service of object
     *
     * @param class-string<Service> $classFqcn
     * @param array<string, mixed> $extraArguments
     *
     * @return Service
     */
    private function createObject(string $classFqcn, array $extraArguments = []): object
    {
        $reflectionClass = new \ReflectionClass($classFqcn);
        $reflectionConstructor = $reflectionClass->getConstructor();

        if ($reflectionConstructor === null) {
            return new $classFqcn();
        }

        $args = [];
        $reflectionParameters = $reflectionConstructor->getParameters();
        foreach ($reflectionParameters as $reflectionParameter) {
            $paramName = $reflectionParameter->getName();

            if (isset($extraArguments[$paramName])) {
                $value = $extraArguments[$paramName];

                if (\is_string($value)) {
                    if ($value[0] === '@') { // service reference
                        $value = str_replace('@', '', $value);
                        \assert(class_exists($value));
                        $value = $this->getInstance($value);
                    } elseif ($value[0] === '%') { // parameter reference
                        $paramAlias = str_replace('%', '', $value);
                        $value = $this->parameters[$classFqcn . $paramAlias] ?? $this->parameters[$paramAlias];
                    }
                }

                $args[$paramName] = $value;

                continue;
            }

            $paramIsMandatory = ! $reflectionParameter->isOptional();

            $typeName = null;
            $typeIsBuiltin = false;
            $typeIsNullable = false;
            $reflectionType = $reflectionParameter->getType();

            if ($reflectionType instanceof ReflectionUnionType) {
                throw new ContainerException("Can't autowire argument '$paramName' of service '$classFqcn' because it has union type.");
            }

            if ($reflectionType instanceof ReflectionNamedType) {
                $typeName = $reflectionType->getName();
                $typeIsBuiltin = $reflectionType->isBuiltin();
                $typeIsNullable = $reflectionType->allowsNull();
            } // else $reflectionType === null (no type specified)

            if ($typeName === null || $typeIsBuiltin) {
                // no type hint or not an object, so try to get a value from the parameters

                // check first if there is an AutowireParameter() attribute to get the paramName from
                $attribute = $reflectionParameter->getAttributes(AutowireParameter::class)[0] ?? null;
                $paramNameFromAttribute = $attribute?->getArguments()[0];

                if (\is_string($paramNameFromAttribute)) {
                    $hasParameter = isset($this->parameters[$paramName]);
                    $value = $this->parameters[$paramName] ?? null;
                } else {
                    // there was no attribute, so use the parameter name, and try it scoped
                    $hasParameter = isset($this->parameters[$classFqcn . $paramName]) || isset($this->parameters[$paramName]);
                    $value = $this->parameters[$classFqcn . $paramName] ?? $this->parameters[$paramName] ?? null;
                }

                if ($hasParameter && $value === null && ! $typeIsNullable) {
                    throw new ContainerException("Constructor argument '$paramName' for class '$classFqcn' is not nullable but a null value was specified through parameters");
                }

                // TODO check unresolvable type mismatch between the parameter value and the argument type
                //  Or try to cast ? if type mistmatch but castable to one another and the file has strict_types=1 we will get a TypeError (todo check)

                if (! $hasParameter && $paramIsMandatory) {
                    $message = "Constructor argument '$paramName' for class '$classFqcn' has no type-hint or is of built-in" .
                        " type '$typeName' but value is not manually specified in the container or the extra arguments.";

                    throw new ContainerException($message);
                }

                if (! $hasParameter) {
                    // because of the condition above, we know the param is always optional here
                    continue;
                }

                $args[$paramName] = $value;

                continue;
            }

            /** @var class-string $typeName */

            // param is a class or interface (internal or userland)
            if (interface_exists($typeName) && ! $this->hasService($typeName)) {
                $msg = "Constructor argument '$paramName' for class '$classFqcn' is declared with the interface " .
                    "'$typeName' but no binding to a concrete implementation for it is set in the container.";

                throw new ContainerException($msg);
            }

            $instance = null;
            try {
                $instance = $this->getInstance($typeName);
            } catch (\Exception $exception) {
                if ($exception::class === 'Exception' && $exception->getCode() === 1) {
                    if (!$paramIsMandatory) { // error during an optional parameter, do nothing
                        continue;
                    }

                    $msg = "Constructor argument '$paramName' for class '$classFqcn' has type '$typeName' " .
                        " but the container don't know how to resolve it.";

                    throw new ContainerException($msg);
                }

                // other exception in the factories, that must be propagated
                throw $exception;
            }

            $args[$paramName] = $instance;
        }

        return new $classFqcn(...$args);
    }

    // --------------------------------------------------
    // Parameters

    /**
     * @var array<string, null|bool|int|float|string|array<mixed>|object>
     */
    private array $parameters = [];

    /**
     * @param bool|int|float|string|array<mixed>|object|null $value
     */
    public function setParameter(string $name, null|bool|int|float|string|array|object $value, string $scope = ''): void
    {
        $this->parameters["$scope$name"] = $value;
    }

    /**
     * @return bool|int|float|string|array<mixed>|object|null
     */
    public function getParameter(string $name, string $scope = ''): null|bool|int|float|string|array|object
    {
        return $this->parameters["$scope$name"] ?? null;
    }

    public function hasParameter(string $name, string $scope = ''): bool
    {
        return isset($this->parameters["$scope$name"]);
    }

    /**
     * @return ($default is null ? null|string : string)
     */
    public function getStringParameter(string $name, null|string $default = '{{ArgNotProvided}}'): null|string
    {
        if (!$this->hasParameter($name)) {
            if ($default === '{{ArgNotProvided}}') {
                throw new \UnexpectedValueException("Parameter '$name' is not set.");
            }

            return $default;
        }

        return (string) $this->parameters[$name];
    }

    /**
     * @return ($default is null ? null|int : int)
     */
    public function getIntParameter(string $name, null|int $default = \PHP_INT_MIN): null|int
    {
        if (!$this->hasParameter($name)) {
            if ($default === \PHP_INT_MIN) {
                throw new \UnexpectedValueException("Parameter '$name' is not set.");
            }

            return $default;
        }

        return (int) $this->parameters[$name];
    }

    /**
     * @return ($default is null ? null|float : float)
     */
    public function getFloatParameter(string $name, null|float $default = \PHP_FLOAT_MIN): null|float
    {
        if (!$this->hasParameter($name)) {
            if ($default === \PHP_FLOAT_MIN) {
                throw new \UnexpectedValueException("Parameter '$name' is not set.");
            }

            return $default;
        }

        return (float) $this->parameters[$name];
    }

    /**
     * @param null|bool $default
     *
     * @return ($default is null ? null|bool : bool)
     */
    public function getBoolParameter(string $name, null|bool|string $default = ''): null|bool
    {
        if (!$this->hasParameter($name)) {
            if (\is_string($default)) {
                throw new \UnexpectedValueException("Parameter '$name' is not set.");
            }

            return $default;
        }

        return (bool) $this->parameters[$name];
    }

    /**
     * @param null|array<mixed> $default
     *
     * @return ($default is null ? null|array<mixed> : array<mixed>)
     */
    public function getArrayParameter(string $name, null|array $default = [false]): null|array
    {
        if (!$this->hasParameter($name)) {
            if ($default === [false]) {
                throw new \UnexpectedValueException("Parameter '$name' is not set.");
            }

            return $default;
        }

        return (array) $this->parameters[$name];
    }

    /**
     * @param null|object $default
     *
     * @return ($default is null ? null|object : object)
     */
    public function getObjectParameter(string $name, null|object|string $default = ''): null|object
    {
        if (!$this->hasParameter($name)) {
            if (\is_string($default)) {
                throw new \UnexpectedValueException("Parameter '$name' is not set.");
            }

            return $default;
        }

        return (object) $this->parameters[$name];
    }
}
