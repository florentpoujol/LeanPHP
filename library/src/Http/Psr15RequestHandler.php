<?php

declare(strict_types=1);

namespace LeanPHP\Http;

use LeanPHP\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Psr15RequestHandler implements RequestHandlerInterface
{
    /**
     * @var array<class-string<MiddlewareInterface>>
     */
    private array $middleware;

    public function __construct(
        private readonly Route $route,
        private readonly Container $container,
        private readonly HttpKernel $httpKernel,
    ) {
        $this->middleware = $route->getMiddleware(); // @phpstan-ignore-line
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var class-string<MiddlewareInterface> $fqcn */
        $fqcn = array_shift($this->middleware);

        if ($fqcn !== null) {
            /** @var MiddlewareInterface $instance */
            $instance = $this->container->get($fqcn);

            return $instance->process($request, $this);

            // The trick here is that we are passing this handler instance to all middleware.
            // So this method will be called multiple times, each time removing a middleware from the stack.

            // If a middleware returns a response without passing the request to the handler
            // the code below never gets called and the response naturally bubble up
            // the stack of middleware that have run, up to Framework::handleRequestThroughPsr15Middleware().
        }

        // If we are here, we did get through all middleware.
        // It is then time to call the controller, then returning the response,
        // which will automatically pass it up the stack of middleware that have run, up to Framework::handleRequestThroughPsr15Middleware().

        return $this->httpKernel->callRouteAction($this->route);
    }

    public function getRoute(): Route
    {
        return $this->route;
    }
}
