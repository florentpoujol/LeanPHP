<?php declare(strict_types=1);

use LeanPHP\Container;
use LeanPHP\Environment;
use LeanPHP\Logging\ResourceLogger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

assert(isset($container) && $container instanceof Container);

$environmentName = $container->getStringParameterOrThrow('environmentName');

// HTTP setup
$container->setFactory(ServerRequestInterface::class, static function (): ServerRequest {
    $psr17Factory = new Psr17Factory();
    $serverRequestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

    $serverRequest = $serverRequestCreator->fromGlobals();
    assert($serverRequest instanceof ServerRequest);

    return $serverRequest;
});

// views
$container->setParameter('baseViewPath', realpath(__DIR__ . '/../resources/views')); // for the ViewRenderer service

// logging
$container->setParameter('logFilePath', Environment::getStringOrDefault('LOG_FILE', __DIR__ . '/../var/logs/main.log')); // in Production, the env var can be STDERR for instance
$container->bind(LoggerInterface::class, ResourceLogger::class);

// database
$container->setFactory(\PDO::class, function (): \PDO {
    $dsn = Environment::getStringOrThrow('DATABASE_DSN');

    // Inside a docker container (with either Franken or nginx),
    // using a relative path for the SQLite database doesn't work so we have to force an absolute path
    // that works in all cases (Docker or not, CLI or the web)
    if (
        str_starts_with($dsn, 'sqlite')
        && $dsn !== 'sqlite::memory:'
    ) {
        [, $path] = explode(':', $dsn);
        if (!str_starts_with($path, '/')) {
            $dsn = 'sqlite:' . dirname(__DIR__) . '/' . $path;
        }
    }

    return new \PDO(
        $dsn,
        Environment::getStringOrNull('DATABASE_USERNAME'),
        Environment::getStringOrNull('DATABASE_PASSWORD'),
        [
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ],
    );
});
// customize the name of the migration table
// $container->setParameter('migrationTableName', 'my_custom_name');
