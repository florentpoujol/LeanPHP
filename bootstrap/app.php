<?php

/*
 * This file reads the environment files, if they exist
 * and then initiate the DI container.
 *
 * It doesn't create an instance of an Application class
 */

declare(strict_types=1);

use LeanPHP\Container;
use LeanPHP\Environment;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Psr\Log\LoggerInterface;

$environmentName = null;
if (defined('APP_ENV_OVERRIDE')) {
    $environmentName = APP_ENV_OVERRIDE;
    assert(is_string($environmentName));
}

if ($environmentName !== 'test') {
    $whoopsErrorHandler = new Run();
    if (\PHP_SAPI === 'cli') {
        $whoopsErrorHandler->pushHandler(new PlainTextHandler());
    } else {
        $whoopsErrorHandler->pushHandler(new PrettyPageHandler());
    }
    // see doc for JSON or ajax requests
    $whoopsErrorHandler->register();
}


// --------------------------------------------------
// Read environment

Environment::readFileIfExists(__DIR__ . '/../.env');
if ($environmentName === null) {
    $environmentName = Environment::getStringOrDefault('APP_ENV', 'prod');
}

$envNameRegex = '/^[a-z_-]{3,20}$/';
if ((int)preg_match($envNameRegex, $environmentName) === 0) {
    throw new \Exception("The environment name '$environmentName' doesn't respect the regex '$envNameRegex'. Check the value of the APP_ENV variable.");
}

Environment::readFileIfExists(__DIR__ . "/../.env.$environmentName");
Environment::readFileIfExists(__DIR__ . "/../.env.$environmentName.local");

// Alternatively use https://github.com/vlucas/phpdotenv instead of the built-in env file reader:
// Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env')->safeload();

// --------------------------------------------------
// Load bootstrap

$container = new Container();
$container->setParameter('environmentName', $environmentName);

require __DIR__ . '/../bootstrap/factories.php'; // fill the container

if ($environmentName !== 'test') {
    assert(isset($whoopsErrorHandler));
    $whoopsErrorHandler->pushHandler(new CallbackHandler(function (\Throwable $exception) use ($container): void {
        $logger = $container->get(LoggerInterface::class);
        $message = $exception->getMessage() . ' ' . $exception->getFile() . ':' . $exception->getLine();
        $logger->error($message);
    }));
}
