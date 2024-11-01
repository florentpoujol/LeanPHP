<?php declare(strict_types=1);

namespace LeanPHP\Container;

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
 * Note Florent 28/09/2024 I'm doing something very wrong with the genericity here because it doesn't work
 *
 * @template ServiceType of object
 */
final class Container
{
    /**
     * @var array<class-string<ServiceType>, (callable(): ServiceType)|class-string<ServiceType>>
     */
    private array $bindings = [
        ResponseInterface::class => Response::class,
        RequestInterface::class => Request::class, // client request
        \DateTimeInterface::class => \DateTimeImmutable::class,
        ValidatorInterface::class => Validator::class,
    ];

    /**
     * @var array<class-string<ServiceType>, (callable(): ServiceType)|class-string<ServiceType>>
     */
    private array $singletonBindings = [
        HasherInterface::class => BuiltInPasswordHasher::class,
        EntityHydratorInterface::class => EntityHydrator::class,
    ];

    /**
     * Values cached by get().
     * Typically, object instances but may be any values returned by closures or found in services.
     *
     * @var array<class-string<ServiceType>, ServiceType>
     */
    private array $instances = [];

    private function __construct()
    {
        $this->instances[self::class] = $this; // @phpstan-ignore-line
    }

    private static ?self $self = null; // @phpstan-ignore-line (Property LeanPHP\Container::$self with generic class LeanPHP\Container does not specify its types: ServiceType)

    public static function getInstance(): self // @phpstan-ignore-line (basically same as above)
    {
        return self::$self ??= new self();
    }

    /**
     * @param class-string<ServiceType> $abstractFQCN
     * @param class-string<ServiceType> $concreteFQCN
     */
    public function bind(string $abstractFQCN, string $concreteFQCN, bool $isSingleton = true): void
    {
        if ($isSingleton) {
            $this->singletonBindings[$abstractFQCN] = $concreteFQCN;
        } else {
            $this->bindings[$abstractFQCN] = $concreteFQCN;
        }
    }

    /**
     * @param class-string<ServiceType> $abstractFQCN
     *
     * @return null|class-string<ServiceType>|(callable(): ServiceType)
     */
    public function getBinding(string $abstractFQCN): null|string|callable
    {
        return $this->bindings[$abstractFQCN] ?? null;
    }

    /**
     * @param class-string<ServiceType> $abstractFQCN
     * @param callable(): ServiceType $concreteFactory
     */
    public function setFactory(string $abstractFQCN, callable $concreteFactory, bool $isSingleton = true): void
    {
        if ($isSingleton) {
            $this->singletonBindings[$abstractFQCN] = $concreteFactory;
        } else {
            $this->bindings[$abstractFQCN] = $concreteFactory;
        }
    }

    /**
     * @param ServiceType $instance
     * @param null|string|class-string<ServiceType> $alias
     */
    public function setInstance(object $instance, null|string $alias = null): void
    {
        $this->instances[$alias ?? $instance::class] = $instance; // @phpstan-ignore-line (gotta fixup the alias system)
    }

    /**
     * @param class-string<ServiceType> $id
     */
    public function has(string $id): bool
    {
        return
               isset($this->instances[$id])
            || isset($this->singletonBindings[$id])
            || isset($this->bindings[$id]);
    }

    /**
     * @template ServiceFQCN of object
     *
     * @param class-string<ServiceFQCN> $id
     *
     * @return ServiceFQCN
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id]; // @phpstan-ignore-line
        }

        $concrete = $this->make($id); // @phpstan-ignore-line
        if ($concrete === null) {
            throw new ContainerException("Service '$id' couldn't be resolved", 1);
        }

        if (isset($this->singletonBindings[$id])) {
            $this->instances[$id] = $concrete; // @phpstan-ignore-line
            $this->instances[$concrete::class] = $concrete;
        }

        return $concrete; // @phpstan-ignore-line
    }

    /**
     * Returns a new instance of object or call again a callable.
     *
     * @param class-string<ServiceType> $id
     * @param array<string, mixed> $extraArguments
     *
     * @return null|ServiceType
     *
     * @throws \Exception when a service name couldn't be resolved
     */
    public function make(string $id, array $extraArguments = []): ?object
    {
        if (! isset($this->singletonBindings[$id]) && ! isset($this->bindings[$id])) {
            if (class_exists($id)) {
                return $this->createObject($id, $extraArguments);
            }

            throw new ContainerException("Factory or concrete class FQCN for abstract '$id' not found.");
        }

        $bindings = array_merge($this->singletonBindings, $this->bindings);

        $value = $bindings[$id];

        if (\is_callable($value)) {
            // TODO inject dependencies in the factory too
            return $value($this, $extraArguments);
        }

        // $value is a concrete class FQCN, which may also be and alias to other service

        // resolve alias as deep as possible
        while (isset($bindings[$value])) {
            $value = $bindings[$value];

            if (\is_callable($value)) {
                return $value($this, $extraArguments);
            }
        }

        if (class_exists($value)) {
            return $this->createObject($value, $extraArguments);
        }

        throw new ContainerException("Service '$id' resolve to a string value '$value' that is neither another known service nor a class name.");
    }

    /**
     * @param class-string<ServiceType> $classFqcn
     * @param array<string, mixed> $extraArguments
     *
     * @return ServiceType
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
                        $value = $this->get($value);
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

            /** @var class-string<ServiceType> $typeName */

            // param is a class or interface (internal or userland)
            if (interface_exists($typeName) && ! $this->has($typeName)) {
                $msg = "Constructor argument '$paramName' for class '$classFqcn' is declared with the interface " .
                    "'$typeName' but no binding to a concrete implementation for it is set in the container.";

                throw new ContainerException($msg);
            }

            $instance = null;
            try {
                $instance = $this->get($typeName);
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
