<?php

declare(strict_types=1);

use LeanPHP\Container;
use LeanPHP\Environment;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$whoopsErrorHandler = new Run();
if (\PHP_SAPI === 'cli') {
    $whoopsErrorHandler->pushHandler(new PlainTextHandler());
} else {
    $whoopsErrorHandler->pushHandler(new PrettyPageHandler());
}
// see doc for JSON or ajax requests
$whoopsErrorHandler->register();


// --------------------------------------------------
// Read environment

Environment::readFileIfExists(__DIR__ . '/../.env');

if (defined('APP_ENV_OVERRIDE')) {
    $environmentName = APP_ENV_OVERRIDE;
    assert(is_string($environmentName));
} else {
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

require_once __DIR__ . '/../bootstrap/factories.php'; // fill the container

$whoopsErrorHandler->pushHandler(new CallbackHandler(function (\Throwable $exception) use ($container): void {
    $logger = $container->get(LoggerInterface::class);
    $message = $exception->getMessage() . ' ' . $exception->getFile() . ':' . $exception->getLine();
    $logger->error($message);
}));

