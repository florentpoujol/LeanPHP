# Dependency Injection Container

`\LeanPHP\Container` is a dependency injection container.

It is a service which main use is know how to instantiate objects, and how to automatically fill method parameters.

This allows to use **dependency injection** and **autowiring** in the application.   

That means you will not have to instantiate most objects you interact with yourself, but you will merely declare them as constructor arguments of your service.   
When your service is itself instantiated through the container, all its dependencies will be resolved and instantiated through the container too, recursively.

The type of the dependencies are not limited to concrete implementation of objects, but they can be interfaces or even scalar values.

For all that to work, the container needs to be configured with bindings, factories and parameters.    
Properly building the container is the main application bootstrap phase.

## Binding interfaces to concrete implementations

Declaring you service dependencies with interfaces can be useful, because it allows to swap the implementation of the underlying service later on in the project, or based on the environment.  
A main use for this is to use a different implementation during tests.

For that to work, you need to tell the container which concrete implementation to use for every interface.  

Pass the interface and the concrete class fully qualified class names to the `bind()` method.  
Example: 
```php
$container->bind(\App\MyInterface::class, \App\MyService::class);
```

In your controller for instance, you can typehint the constructor for the `MyInterface`, you will receive a `MyService` instance.

If, in the container you swap the `MyService` class by any other, you will receive that new class in your controller without changing the type declaration or how you use the service.

**All services are considered singleton by default**. If that shouldn't be the case, set the third `$isSingleton` argument to `false`.

## Providing factory functions

When a class can not (or you don't want to) be created automatically by the container, you can provide a factory, in the form of any callable, that will just return the instance, to the `setFactory()` method .

```php
$container->setFactory(MyInterface::class, function (Container $container, array $extraArguments = []): MyService {
    // do some stuff
    
    return new MyService(
        // pass some stuff
    );
});
```

The callable gets two arguments: the instance of the container itself and whatever is passed as second argument of the container's `get()` or `make()` methods.

Remember that the callable doesn't need to be a closure, but can be any callable, including invocable classes, static methods, etc...

As with the `bind` method all services are considered singleton by default. If that shouldn't be the case, set the third `$isSingleton` argument to `false`.

## Resolving objects out of the container

Its main usage will be through autowiring, but you can also use the container directly to resolve objects.

You can autowire the Container or get it via the Framework's `getContainer()` methods :

```php
// In a service
__construct(
    private \LeanPHP\Container\Container::class $container,
)

// or anywhere else
$container = \LeanPHP\Container\Container::get();
```

Then you can use its two methods :
- `get(string $serviceName, array $extraArguments = []): object` 
- `make(string $serviceName, array $extraArguments = []): ?object`

`get()` will throw an `Exception` if the service can't be resolved, where `make()` can return `null`.  
Also `get()` will always return the same instance for services that are marked as singleton.
`make()` will always create a new instance, even for services marked as singleton.

The second argument is used to directly pass values for the constructor arguments when they can't be autowired.  
It is expected to be an associative array where the keys match some of the constructor's arguments names. 

## Filling scalar values with parameters

When a constructor argument isn't an interface or a class, and has a primitive type or no type at all the container can not know on its own how to build the value for that.

When directly resolving instances via the `get()` or `make()` method, the value can directly be provided via the `extraArguments` parameter.  
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
$container->get(MyClass::class, [
    'somePrimitiveValue' => 10,
]);
```

When autowiring,  the container will try to find a parameter that has the same name as the argument.  
Ie:
```php
final readonly class MyClass
{
    public function __construct(
        private int $somePrimitiveValue,
    ) {}
}

$container->setParameter('somePrimitiveValue', 10);

// now the MyClass object can be autowired
```

### Scope parameters per class

To prevent conflict when several classes have a primitive argument with the same name but not the same value, parameters can be scoped to a specific class.

To do so, specify the class FQCN as the third argument of the `set/get/HasParameter()` methods.  

When building an object the scoped parameter will be tried first and them it will fallback to the global one.

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
When using the `$extraArguments` argument of the `get/make` methods, you can set an argument to point to a parameter that has a different name by prefixing it with `%`.

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
$container->get(MyClass::class, [
    'appName' => '%app.name',
]);
```

### Manually getting parameters out of the container

You can use the `getParameter(string $name): mixed` method, or any of the typed methods.  
The similar methods are available for the `string`, `int`, `float`, `bool`, `array` and `object` types.

Ie for `int`:
```php
$container->getIntParameterOrThrow('name');
$container->getIntParameterOrNull('name');
$container->getIntParameterOrDefault('name', 42);
```


## Use parameter as generic config store

There is no restriction on parameter names.
The scope of the parameters previous system isn't actually limited to FQCN, but to any strings.

Also, the parameter name supports one level of dot notation.  
The first bit is considered as the scope.

This can be used
```php
// with the scope directly in the same
$container->setParameter('app.name', 'dev');
// same as
$container->setParameter('name', 'dev', 'app');  

$container->getParameter('app.name');
// same as
$container->getParameter('name', 'app'); 
```

Note that the typed methods to do support the scope argument, you can only use them with the dot notation.

Note that with this notation, you need to alias the parameter, or use the Autowire attribute on the argument.

```php
final class MyClass
{
    public function __construct(
        #[AutowireParameter('app.name')]
        private string $appName
    ) {}
}

// doesn't need aliasing or attribute
$container->setParameter('appName', 'the app name');

// does need aliasing
$container->setParameter('app.name', 'the app name');

// or with the attribute
$container->get(MyClass::class, [
    'appName' => '%app.name',
]);
```
