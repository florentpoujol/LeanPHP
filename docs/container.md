# Dependency Injection Container

`\LeanPHP\Container` is a dependency injection container.

The two main uses of a DI Container is to return a working object instance when asked for an interface, as well as autowiring (automatically filling) constructor arguments when instantiating a new instance.

## Aliasing concrete classes to interfaces

Pass the interface and the concrete class fully qualified class names to the `bind()` method.

Example: 
```php
$container->bind(\App\MyInterface::class, \App\MyService::class);
```

In your controller, you can typehint the constructor for the `MyInterface`, you will receive a `MyService` instance.

If, in the container you swap the `MyService` class by any other, you will receive that new class in your controller without changing the type declaration.

All services are considered singleton by default. If that shouldn't be the case, set the third `$isSingleton` argument to `false`.

## Providing actual factory functions

When a class can not (or you don't want to) be created automatically by the container, you can provide a factory, in the form of any callable, that will just return the instance, to the `setFactory()` method .

```php
$container->setFactory(MyInterface::class, function (Container $container, array $extraArguments = []): MyService {
    // do some stuff
    
    return new MyService(
        // pass some stuff
    );
});
```

The callable gets two arguments : the instance of the container itself and whatever is passed as second argument of the container's `get()` or `make()` methods.

Remember that the callable doesn't need to be a closure, but can be any callable, including invocable classes, static methods, etc...

As with the `bind` method all services are considered singleton by default. If that shouldn't be the case, set the third `$isSingleton` argument to `false`.

## Resolving objects out of the container

Its main usage will be through autowiring, but you can also use the container directly to resolve objects.

You can autowire the Container or get it via the Framework's `getContainer()` methods :

```php
// In a service
__construct(
    private \LeanPHP\Container::class $container,
)

// or anywhere else
$container = \LeanPHP\Container::self();
```

Then you can use its two methods :
- `get(string $serviceName, array $extraArguments = []): object` 
- `make(string $serviceName, array $extraArguments = []): ?object`

`get()` will throw an `Exception` if the service can't be resolved, where `make()` can return `null`.  
Also `get()` will always return the same instance for services that are marked as singleton.
`make()` will always create a new instance, even for services marked as singleton.

The second argument is used to directly pass values for the constructor arguments when they can't be autowired.  
It is expected to be an associative array where the keys match some of the constructor's arguments names. 

## Parameters

When a constructor argument isn't an interface or a class, and has a primitive type or no type at all the container can not know on its own how to build the value for that, unless they are provided via the `$extraArguments` argument of the `get()` or `make()` methods, or they are set as parameters in the container. 

Example:
```php
final class MyClass
{
    public function __construct(
        private SomeClass $someClass,
        private int $somePrimitiveValue
    ) {}
}

$container->get(MyClass::class); 
// would throw an Exception because the container doesn't know which value to pass to `$somePrimitiveValue`

$container->get(MyClass::class, [
    'somePrimitiveValue' => 10,
]);
// this would work

// if the value may appear in several controller and is known in advance it can be set directly in the container
$container->setParameter('somePrimitiveValue', 10);
$container->get(MyClass::class); 
// this would also work
```

### Parameter alias

Using the `$extraArguments` argument, you can set an argument to point to a parameter that has a different name, if you prefix it with `%`.

Example:
```php
final class MyClass
{
    public function __construct(
        private SomeClass $someClass,
        private int $somePrimitiveValue
    ) {}
}

// if the value may appear in several controller and is known in advance it can be set directly in the container
$container->setParameter('someOtherParam', 10);
$container->get(MyClass::class, [
    'somePrimitiveValue' => '%someOtherParam',
]); 
```




