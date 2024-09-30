<?php declare(strict_types=1);

use LeanPHP\Container;
use LeanPHP\Logging\ResourceLogger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use LeanPHP\Environment;

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

$container->bind(ResponseInterface::class, Response::class);
$container->bind(RequestInterface::class, Request::class); // client request

// views
$container->setParameter('baseViewPath', realpath(__DIR__ . '/../resources/views')); // for the ViewRenderer service

// logging
$container->setParameter('logFilePath', Environment::getStringOrDefault('LOG_FILE', __DIR__ . '/../var/logs/main.log')); // in Production, the env var can be STDERR for instance
$container->bind(LoggerInterface::class, ResourceLogger::class);
