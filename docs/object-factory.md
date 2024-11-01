# Object Factory (Dependency Injection / Service container)

`\LeanPHP\ObjectFactory` is what you would typically call a Dependency Injection container, or service container.

It is a service that knows how to build/instantiate objects, and how to automatically fill method parameters.

This allows to use **dependency injection** and **autowiring** in the application.   

That means you will not have to instantiate most objects you interact with yourself, but you will merely declare them as constructor arguments of your service.   
When your service is itself instantiated through the container, all its dependencies will be resolved and instantiated through the container too, recursively.

The type of the dependencies are not limited to concrete implementation of objects, but they can be interfaces or even scalar values.

For all that to work, the container needs to be configured with **parameters**, **factories** and **aliases**.    
Properly building the container is the main application bootstrap phase.

## Note on the term Singleton

Singleton is a design pattern that actively prevent to have several instances of a given class.

In this documentation, singleton is used to define a class that **will not** be instantiated more than once via the ObjectFactory, even if the class do not technically implement the Singleton design pattern.

## Resolving objects out of the container

Its main usage will be through autowiring, but you can also use the container directly to resolve objects.

You can autowire the Container or get it via the Framework's `getContainer()` methods :

```php
// In a service
__construct(
    private \LeanPHP\Container\Container::class $container,
)

// or anywhere else
$container = \LeanPHP\Container\Container::getInstance();
```

Then you can use its two methods :
- `getInstance(string $serviceName, array $extraArguments = []): object`
- `makeInstance(string $serviceName, array $extraArguments = []): ?object`

`getInstance()` will throw a `ContainerException` if the service can't be resolved, where `makeInstance()` can return `null`.  
Also `getInstance()` will always return the same instance for services that are marked as singleton.
`makeInstance()` will always create a new instance, even for services marked as singleton.

The second argument is used to directly pass values for the constructor arguments when they can't be autowired.  
It is expected to be an associative array where the keys match some of the constructor's arguments names.

## Setting instance directly in the container

If you have pre-built object instances, you can set tehm in the container with the `setInstance(object $object, ?string $alias = null)` method.

```php
$user = new User(); // the logged-in user

$container->setInstance($user);

// give them alias with the second argument
```

## Filling scalar values with parameters

When a constructor argument has a primitive type or no type at all, the container can not know on its own what value to pass to that argument.

When directly resolving instances via the `ge/MakeInstance()` methods, the value can directly be provided via the `$extraArguments` parameter.  
Ie:
```php
final readonly class MyClass
{
    public function __construct(
        private int $somePrimitiveValue,
    ) {}
}

// would throw an ContainerException because the container doesn't know what value to pass to `$somePrimitiveValue`
$container->getInstance(MyClass::class); 

// but this would work
$container->getInstance(MyClass::class, [
    'somePrimitiveValue' => 10,
]);
```

When autowiring, the container will try to find **a parameter** that has the same name as the argument.  

A parameter is a scalar or array or object value set in the container via the `setParameter(string $key, scalar|array $value)` method.

Ie:
```php
final readonly class MyClass
{
    public function __construct(
        private int $somePrimitiveValue,
    ) {}
}

$container->setParameter('somePrimitiveValue', 10);

// now the MyClass object can be built without giving a specific value to the argument 
$container->getInstance(MyClass::class); 
```

### Scope parameters per class

To prevent conflict when several classes have a primitive arguments with the same name but not the same value, parameters can be scoped to a specific class.

To do so, specify the class FQCN as the third argument of the `set/get/HasParameter()` methods.

When building an object, the scoped parameter will be tried first and them it will fallback to the global one.

Ie:
```php
final readonly class MyClass
{
    public function __construct(
        private int $somePrimitiveValue,
    ) {}
}

final readonly class MyOtherClass
{
    public function __construct(
        private string $somePrimitiveValue,
    ) {}
}

// if the value may appear in several controller and is known in advance it can be set directly in the container
$container->setParameter('somePrimitiveValue', 10);
$container->setParameter('somePrimitiveValue', 'foo', MyOtherClass::class);

// this works
$container->getInstance(MyClass::class); // will use the global parameter since none is specified for MyClass
$container->getInstance(MyOtherClass::class); // will use the parameter specific to MyOtherClass
```

### When parameter names are different from the argument names

The parameter and the argument are not required to have the same name.

When autowiring, you can use the `AutowireParameter(string $paramName)` attribute to specify the parameter to get the value from.  
When using the `$extraArguments` argument of the `get/makeInstance()` methods, you can set an argument to point to a parameter that has a different name by prefixing it with `%`.

Ie:
```php
final readonly class MyClass
{
    public function __construct(
        #[AutowireParameter('app.name')]
        private string $appName,
    ) {}
}

// there is no restriction on parameter name.
// Here the dot has no special meaning, but it's a little nicer to read "app.name" than "appName" for instance
$container->setParameter('app.name', 'the app name');

// with the attribute
$container->getInstance(MyClass::class);

// without the attribute, 
$container->getInstance(MyClass::class, [
    'appName' => '%app.name',
]);
```

### Manually getting parameters out of the container

You can use the `getParameter(string $name): mixed` method, or any of the typed methods.  
The similar methods are available for the `string`, `int`, `float`, `bool`, `array` and `object` types.

Ie for `int`:
```php
// without the second argument, the method will throw an exception if the parameter doesn't exist
$container->getIntParameter('name');

// all methods have a second $default parameter whose value will be returned if the parameter doesn't exist
$container->getIntParameter('name', 42);

// the $default argument can also be null.
// Only in that case, PHPStan understand that the method return value is nullable
$container->getIntParameter('name', null);
```

## Providing factory functions

When a class can not (or you don't want to) be created automatically by the container, you can provide a factory, in the form of any callable, that must return the instance, to the `setFactory(string $serviceName, callable $factory)` method.

The `serviceName` argument can be a concrete implementation, an interface or an alias.

```php
$container->setFactory(MyService::class, function (Container $container, array $extraArguments = []): MyService {
    // do some stuff
    
    return new MyService(
        // pass some stuff
    );
});
```

The callable gets two arguments: the instance of the container itself and whatever is passed as second argument of the container's `get/makeInstance()` methods.

The callable doesn't need to be a closure, but can be any callable, including invocable classes, static methods, etc...

**All services are considered singleton by default**. If that shouldn't be the case, set the third `$isSingleton` argument to `false`.

## Aliasing service names

## Binding interfaces to concrete implementations

Declaring your service dependencies with interfaces can be useful, because it allows to swap the implementation of the underlying service later on in the project, or based on the environment.  
A main use for this is to use a different implementation during tests.

For that to work, you need to tell the container which concrete implementation to use for every interface.  

Pass the interface and the concrete class fully qualified class names to the `alias(string $aliasName, string $aliasedName)` method.  
Example: 
```php
$container->alias(\App\MyInterface::class, \App\MyService::class);
```

In your controller for instance, you can typehint the constructor for the `MyInterface`, you will receive a `MyService` instance.

If, in the container you swap the `MyService` class by any other, you will receive that new class in your controller without changing the type declaration or how you use the service.

**All services are considered singleton by default**. If that shouldn't be the case, set the third `$isSingleton` argument to `false`.

## Distinguish between several implementation that have the same interfaces 

Sometimes, your app needs several instances of the same concrete implementation but that differ on how they are build.

We can not distinguish them from their type, or interface, but you still need to be able to inject specific instances to specific services (and the strategy pattern can not be used to prevent that situation).

In this case, you must give an arbitrary name to each implementation so that you can specifically request one of them via an attribute.

Example with two loggers that are the same class, but logs in a different file.
```php
// in the container setup
$container->setFactory('logger.error', function () {
    return new FileLogger('/var/logs/errors.log'); 
});

$container->setFactory('logger.info', function () {
    return new FileLogger('/var/logs/info.log'); 
});

// feel free to alias interfaces to any other alias, so that now, the info logger is the default implementation for that interface
$container->alias(LoggerInterface::class, 'logger.info');

// in your class
final readonly class MyClass
{
    public function __construct(
        #[AutowireService('logger.error')]
        private LoggerInterface $logger, // without the attribute, the "info logger" would be received 
    ) {}
}
```
