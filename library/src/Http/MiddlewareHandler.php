<?php

declare(strict_types=1);

namespace LeanPHP\Http;

use LeanPHP\Container\Container;

/**
 * This is basically a PSR15 middleware handler, but that handles LeanPHP objects
 */
final class MiddlewareHandler
{
    /**
     * @var array<class-string<HttpMiddlewareInterface>>
     */
    private array $middleware;

    public function __construct(
        private readonly Route $route,
        private readonly Container $container,
        private readonly HttpKernel $httpKernel,
    ) {
        $this->middleware = $route->getMiddleware();
    }

    public function handle(ServerRequest $request): AbstractResponse
    {
        $middlewareFqcn = array_shift($this->middleware);

        if ($middlewareFqcn !== null) {
            /** @var HttpMiddlewareInterface $middlewareInstance */
            $middlewareInstance = $this->container->get($middlewareFqcn);

            return $middlewareInstance->handle($request, $this);

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
