<?php declare(strict_types=1);
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// assert($container instanceof Container);

$container->setFactory(ServerRequestInterface::class, static function (): ServerRequest {
    $psr17Factory = new Psr17Factory();
    $serverRequestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

    $serverRequest = $serverRequestCreator->fromGlobals();
    assert($serverRequest instanceof ServerRequest);

    return $serverRequest;
});

$container->bind(ResponseInterface::class, Response::class);
$container->bind(RequestInterface::class, Request::class); // client request