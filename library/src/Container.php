<?php declare(strict_types=1);

namespace LeanPHP;

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
     * @param class-string<ServiceType> $abstractFQCN
     * @param ServiceType $instance
     */
    public function setInstance(string $abstractFQCN, object $instance): void
    {
        $this->instances[$abstractFQCN] = $instance;
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
            throw new \Exception("Service '$id' couldn't be resolved", 1);
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

            throw new \Exception("Factory or concrete class FQCN for abstract '$id' not found.");
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

        throw new \Exception("Service '$id' resolve to a string value '$value' that is neither another known service nor a class name.");
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
                        $value = $this->parameters[str_replace('%', '', $value)];
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
                throw new \Exception("Can't autowire argument '$paramName' of service '$classFqcn' because it has union type.");
            }

            if ($reflectionType instanceof ReflectionNamedType) {
                $typeName = $reflectionType->getName();
                $typeIsBuiltin = $reflectionType->isBuiltin();
                $typeIsNullable = $reflectionType->allowsNull();
            } // else $reflectionType === null (no type specified)

            if ($typeName === null || $typeIsBuiltin) {
                // no type hint or not an object, so try to get a value from the parameters
                $hasParameter = isset($this->parameters[$paramName]);
                $value = $this->parameters[$paramName] ?? null;

                if ($hasParameter && $value === null && ! $typeIsNullable) {
                    throw new \Exception("Constructor argument '$paramName' for class '$classFqcn' is not nullable but a null value was specified through parameters");
                }

                // TODO check unresolvable type mismatch between the parameter value and the argument type
                //  Or try to cast ? if type mistmatch but castable to one another and the file has strict_types=1 we will get a TypeError

                if (! $hasParameter && $paramIsMandatory) {
                    $message = "Constructor argument '$paramName' for class '$classFqcn' has no type-hint or is of built-in" .
                        " type '$typeName' but value is not manually specified in the container or the extra arguments.";

                    throw new \Exception($message);
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

                throw new \Exception($msg);
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

                    throw new \Exception($msg);
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
    public function setParameter(string $name, null|bool|int|float|string|array|object $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @return bool|int|float|string|array<mixed>|object|null
     */
    public function getParameter(string $name): null|bool|int|float|string|array|object
    {
        return $this->parameters[$name] ?? null;
    }

    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    public function getStringParameterOrThrow(string $name): string
    {
        if (!$this->hasParameter($name)) {
            throw new \UnexpectedValueException("Parameter '$name' is not set.");
        }

        return (string) $this->parameters[$name];
    }

    public function getStringParameterOrNull(string $name): ?string
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        return (string) $this->parameters[$name];
    }

    public function getStringParameterOrDefault(string $name, string $default): string
    {
        if (!$this->hasParameter($name)) {
            return $default;
        }

        return (string) $this->parameters[$name];
    }

    // --------------------------------------------------

    public function getIntParameterOrThrow(string $name): int
    {
        if (!$this->hasParameter($name)) {
            throw new \UnexpectedValueException("Parameter '$name' is not set.");
        }

        return (int) $this->parameters[$name];
    }

    public function getIntParameterOrNull(string $name): ?int
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        return (int) $this->parameters[$name];
    }

    public function getIntParameterOrDefault(string $name, int $default): int
    {
        if (!$this->hasParameter($name)) {
            return $default;
        }

        return (int) $this->parameters[$name];
    }

    // --------------------------------------------------

    public function getFloatParameterOrThrow(string $name): float
    {
        if (!$this->hasParameter($name)) {
            throw new \UnexpectedValueException("Parameter '$name' is not set.");
        }

        return (float) $this->parameters[$name];
    }

    public function getFloatParameterOrNull(string $name): ?float
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        return (float) $this->parameters[$name];
    }

    public function getFloatParameterOrDefault(string $name, float $default): float
    {
        if (!$this->hasParameter($name)) {
            return $default;
        }

        return (float) $this->parameters[$name];
    }

    // --------------------------------------------------

    public function getBoolParameterOrThrow(string $name): bool
    {
        if (!$this->hasParameter($name)) {
            throw new \UnexpectedValueException("Parameter '$name' is not set.");
        }

        return (bool) $this->parameters[$name];
    }

    public function getBoolParameterOrNull(string $name): ?bool
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        return (bool) $this->parameters[$name];
    }

    public function getBoolParameterOrDefault(string $name, bool $default): bool
    {
        if (!$this->hasParameter($name)) {
            return $default;
        }

        return (bool) $this->parameters[$name];
    }

    // --------------------------------------------------

    /**
     * @return array<mixed>
     */
    public function getArrayParameterOrThrow(string $name): array
    {
        if (!$this->hasParameter($name)) {
            throw new \UnexpectedValueException("Parameter '$name' is not set.");
        }

        return (array) $this->parameters[$name];
    }

    /**
     * @return null|array<mixed>
     */
    public function getArrayParameterOrNull(string $name): ?array
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        return (array) $this->parameters[$name];
    }

    /**
     * @param array<mixed> $default
     *
     * @return array<mixed>
     */
    public function getArrayParameterOrDefault(string $name, array $default): array
    {
        if (!$this->hasParameter($name)) {
            return $default;
        }

        return (array) $this->parameters[$name];
    }

    // --------------------------------------------------

    public function getObjectParameterOrThrow(string $name): object
    {
        if (!$this->hasParameter($name)) {
            throw new \UnexpectedValueException("Parameter '$name' is not set.");
        }

        return (object) $this->parameters[$name];
    }

    public function getObjectParameterOrNull(string $name): ?object
    {
        if (!$this->hasParameter($name)) {
            return null;
        }

        return (object) $this->parameters[$name];
    }

    public function getObjectParameterOrDefault(string $name, object $default): object
    {
        if (!$this->hasParameter($name)) {
            return $default;
        }

        return (object) $this->parameters[$name];
    }
}
