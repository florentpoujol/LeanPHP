# Environment and configuration

## Environment

To get the value from environment variable, PHP has the built-in [`getenv()` function](https://www.php.net/manual/en/function.getenv.php) and the [`$_ENV` superglobal](https://www.php.net/manual/en/reserved.variables.environment.php).

A disadvantage regarding static analysis is that their result isn't typed.

The `Environment` class provide convenience static methods to read the environment (the value are coming from `getenv()`):

```php
Environment::getStringOrThrow(string $name): string // throws an exception if the name isn't found
Environment::getStringOrNull(string $name): ?string // returns null if the name isn't found
Environment::getStringOrDefault(string $name, string $default): string // returns the default value if the name isn't found

// same thing for int, float and bool
Environment::getIntOrThrow(string $name): int
Environment::getIntOrNull(string $name): ?int
Environment::getIntOrDefault(string $name, int $default): int

Environment::getFloatOrThrow(string $name): float
Environment::getFloatOrNull(string $name): ?float
Environment::getFloatOrDefault(string $name, float $default): float

Environment::getBoolOrThrow(string $name): bool
Environment::getBoolOrNull(string $name): ?bool
Environment::getBoolOrDefault(string $name, bool $default): bool
// note that the value is cast as-is, so the values "false" and "null" both becomes true
```

### Reading environment files

The class also provide the `readEnvFileIfExists(string $envFilePath): void` static method that reads an environment file with and extract keys and values via a simple regex.    

As such, some restrictions apply on the format for the keys/values :
- keys and their values must be on the same line
- keys and values are trimmed for whitespace
- if you need whitespace at the beginning or end of a value, surround it with single or double quotes
- comments after the value aren't allowed
- interpolation of other environment variable inside values isn't supported

Example:
The `.env` file :
```dotenv
SOME_VAR=some value
 WHITESPACE_ARE_TRIMMED  =  some value
WITH_QUOTES=" some value with whitespace  "

# all lines that don't look like VAR=value are ignored(even without a # in front)
```

For more complex format, you can provide a different regex as the second argument of the method, or use a third party package like the traditional [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) .




## Configuration

In framework-based projects, many behaviors are configured via configuration, which is basically a key/value store.
Configuration is often coupled with the environment since many values will be resolved out of the environment.

One of the most important service being configured in the DI container itself.

In the framework-less projects that you build with LeanPHP, traditional configuration may not be needed and is mostly limited to defining proper service factories for the DI container.

However, you may want to have some configuration for your own app, here is what you can do.  

## Object-based configuration

Any data object could be considered as configuration.  
You can make so that they are build via the Container so that you can type-hint them in your services.  
Or if you don't care about dependency injection, or you are in a situation where it's not applicable, you can just directly instantiate them for instance by a direct call to the `make()` method like below.

Ie:
```php
// the config class
final readonly class MyConfig
{
    public function __construct(
        public string $key,
        public string $otherKey,
    ) {}
}

// build the object in the DI factories
$container->setFactory(MyConfig::class, function() {
    return new MyConfig(
        Environment::getStringOrThrow('SOME_ENV_VAR'),
        Environment::getStringOrDefault('SOME_OTHER_ENV_VAR', 'default value'),
    )
})

// or you can put the factory in the class itself
final readonly class MyConfig
{
    // ...
    public static function make(): self
    {
        return new MyConfig(
            Environment::getStringOrThrow('SOME_ENV_VAR'),
            Environment::getStringOrDefault('SOME_OTHER_ENV_VAR', 'default value'),
        );    
    }
}

$container->setFactory(MyConfig::class, [MyConfig::class, 'make']);
// or just call MyConfig::make()
```

## Container parameters

The DI container, in addition to store instances of services, can store pretty much any scalar values, as well as arrays and object as parameters.

These parameters then become automatically autowirable by name, but you can also just use the container as your configuration store.

To get the value of a parameter, either add it as a constructor argument, or use one of the `get*ParameterOr*()` methods:
```php
$container->setParameter(string $name, null|int|float|string|bool|array|object $value): void
$container->hasParameter(string $name): bool
$container->getParameter(string $name): null|int|float|string|bool|array|object

$container->getStringParameterOrThrow(string $name): string // throws an exception if the parameter isn't found
$container->getStringParameterOrNull(string $name, string $default): string // returns null if the name isn't found
$container->getStringParameterOrDefault(string $name, string $default): string // returns the default value if the name isn't found

// then similar methods for every type: int, float, bool, array and object
```

## Traditional array-based configuration

For bigger use-case, you can use the `ConfigRepository`.
It has the similar typed methods as for the parameter (just without the "parameter" in the name).

Also, it has a factory method that takes a folder as argument. 
It will read every PHP files in that folder and expect them to return an array.

All values returned by these files will be registered with the file name as "prefix".

```php
// a file config/app.php
retrurn [
    'is-staging' => Environment::getBoolOrDefault('IS_STAGING', false),
];

// during bootstrap of you app
$container->setFactory(ConfigRepository::class, [ConfigRepository::class, 'makeFromFolder']);

// then in a service
$config->getBoolOrThrow('app.is-staging'); // false 
```
