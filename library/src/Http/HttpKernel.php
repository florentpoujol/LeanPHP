<?php

declare(strict_types=1);

namespace LeanPHP\Http;

use LeanPHP\Container;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HttpKernel
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    /**
     * @param array<Route> $routes
     */
    public function handle(array $routes, ServerRequest $serverRequest): ResponseInterface
    {
        try {
            $router = new Router($routes);
            $route = $router->resolveRoute($serverRequest->getMethod(), $serverRequest->getUri()->getPath());

            if ($route === null) {
                return new Response(404, body: $serverRequest->getUri()->getPath() . ' not found');
            }

            $this->container->setInstance(Route::class, $route);

            // TODO handle redirects
            if ($route->isRedirect()) {
                $action = $route->getAction();
                \assert(\is_string($action));

                $status = str_starts_with($action, 'redirect-permanent:') ? 301 : 302;
                $location = str_replace(['redirect:', 'redirect-permanent:'], '', $action);

                return new Response($status, ['Location' => $location]);
            }

            if ($route->hasPsr15Middleware()) {
                return $this->handleRequestThroughPsr15Middleware();
            }

            $response = $this->callRouteAction($route);
        } catch (\Throwable $exception) {
            // $exceptionHandler = $this->container->get(ExceptionHandler::class);
            //
            // $exceptionHandler->report($exception);
            //
            // $response = $exceptionHandler->render($exception);
            $response = new Response(500, body: $exception->getMessage() . ' ' . $exception->getFile() . ' ' . $exception->getLine());
        }

        return $response;
    }

    public function handleRequestThroughPsr15Middleware(): ResponseInterface
    {
        $handler = $this->container->get(RequestHandlerInterface::class);

        $serverRequest = $this->container->get(ServerRequestInterface::class);

        return $handler->handle($serverRequest); // see in the handle method for explanation as to why this single line does everything and return the final response, whatever happens in between
    }

    public function callRouteAction(Route $route): ResponseInterface
    {
        /** @var callable|string $action A callable or an "at" string : "Controller@method" */
        $action = $route->getAction();

        if (! \is_callable($action)) {
            // "Controller@method"
            /** @var class-string<object> $fqcn */
            [$fqcn, $method] = explode('@', $action, 2);
            $action = [$this->container->get($fqcn), $method];
            \assert(\is_callable($action));
        }

        return $action(
            ...$route->getActionArguments(), // this unpacks an assoc array and make use of named arguments to inject the proper value taken from the URI segments to the correct argument
        );
    }

    public function sendResponse(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        /**
         * @var array<string> $values
         */
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        $body = $response->getBody();
        $body->rewind();

        echo $body->getContents();

        $body->close();
    }
}
