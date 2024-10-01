# Logging

LeanPHP provide by default three PSR-3-compliant loggers.  
For every use cases that are not covered, use [Monolog](https://github.com/Seldaek/monolog).

## ResourceLogger

`ResourceLogger` is a generic PSR-3 compliant logger that accept as constructor argument either a resource or a file path, and optionally a formatter callable.

Passing a resource directly is useful if you have one already open, or to use built-in ones like `STDOUT` or `STDERR`.

Alternatively, if the file path is  `"STDOUT"` or `"STDERR"`, it will use the built-in resources. This is useful to use the same environment variable locally where it can point to an actual file and in production where its value can just be `"STDERR"` as some cloud platform require.

The formatter callable shall have this signature `(string $level, string $message, array $context = []): string` and return the whole line to be logged without the terminating line-break.

The default formatter will produce line like `[{datetime}] {LEVEL}: {message} {context as json}`.

## SyslogLogger

The `SyslogLogger` is a generic PSR-3 compliant logger that will log with the built-in `syslog()` function. 
It accepts two optional constructor arguments: the file prefix (the first argument of the `openlog()` function), and a formatter callable.

The formatter callable shall have this signature `(int|string $level, string $message, array $context = []): string` and return the whole line to be logged.

The default formatter will produce line like `[{datetime}] {LEVEL}: {message} {context as json}`.

## DailyFileLogger

`DailyFileLogger` will create a file `log-{Y-m-d].log` for each day and will print message with this structure: `[{datetime}] {LEVEL}: {message} {context as json}`.

Ie:

```php
$logger = new \LeanPHP\Logging\DailyFileLogger('logs');

$logger->info("I'm writing a documentation", [
    'page' => 'logging',
]);

// will log in a file "logs/log-2024-09-30.log"
// a message like this one:
// [2024-09-30 14:01:00] INFO: I'm writing a documentation {"page": "logging"}
```

## FAQ

### How to have several autowirable loggers

The `AbstractLoggerDecorator` class decorates a PSR LoggerInterface and implement the interface itself.

You can create classes that extends the AbstractLoggerDecorator and in their factories, pass them a logger as their constructor argument.
Then you can autowire these classes in your code. If you don't want to autowire a concrete implementation, you are free to create an interface for you classes.

```php
interface ErrorLoggerInterface {}
final class ErrorLogger extends AbstractLoggerDecorator implements ErrorLoggerInterface {}

final class InfoLogger extends AbstractLoggerDecorator

$container->setFactory(ErrorLoggerInterface::class, function (): ErrorLogger {
    return new ErrorLogger(
        new ResourceLogger('/logs/error.log'),
    );
});

$container->setFactory(InfoLogger::class, function (): InfoLogger {
    return new InfoLogger(
        new ResourceLogger('/logs/info.log'),
    );
});
```

### How to log some levels in a file and others in another file

The loggers we provide implements the `ConfigurableLogger` interface which allows 
- to set which levels are handled by the logger
- to tell when in a stack if the next loggers in the stack should also handle the message

Also, the `StackLogger` receive several loggers and will log any message to all of them (if they handle it).

Here is an example of a logger that will log the debug, info and notice levels in a file and all other in another file.
```php
$container->setFactory(LoggerInterface::class, function (): MainLogger {
    return new StackLogger([
    
        (new ResourceLogger('/logs/error.log'))
            ->setMinimumLevel(PsrLevel::ERROR)
            ->setHandleNextLoggerInStack(false),
            
        (new ResourceLogger('/logs/info.log'))->setHandledLevels([
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ]) // since the previous logger terminate the stack if it handled the message, here we could not set any level, the effect would be the same
        
    ]);
});

$container->bind(LoggerInterface::class, MainLogger::class);
```
