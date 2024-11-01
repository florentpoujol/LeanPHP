<?php declare(strict_types=1);

namespace LeanPHP\Http;

use LeanPHP\Container\AutowireParameter;
use LeanPHP\Container\Container;
use LeanPHP\EntityHydrator\EntityHydrator;
use LeanPHP\Http\Attributes\MapQueryString;
use LeanPHP\Http\Attributes\MapRouteParameter;
use LeanPHP\Validation\Validator;
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;

final class HttpKernel
{
    public function __construct(
        private readonly Container $container,
    ) {
        $container->setInstance($this);
    }

    /**
     * @param array<Route> $routes
     */
    public function handle(array $routes, ServerRequest $serverRequest): AbstractResponse
    {
        $router = new Router($routes);
        $route = $router->resolveRoute($serverRequest->getMethod(), $serverRequest->getUri()->getPath());

        if ($route === null) {
            return new Response(404, body: $serverRequest->getUri()->getPath() . ' not found');
        }

        $this->container->setInstance($route);

        // TODO handle redirects
        if ($route->isRedirect()) {
            $action = $route->getAction();
            \assert(\is_string($action));

            $status = str_starts_with($action, 'redirect-permanent:') ? 301 : 302;
            $location = str_replace(['redirect:', 'redirect-permanent:'], '', $action);

            return new Response($status, ['Location' => $location]);
        }

        return $this->handleRequestThroughMiddleware($route, $serverRequest);
    }

    private function handleRequestThroughMiddleware(Route $route, ServerRequest $serverRequest): AbstractResponse
    {
        $handler = new MiddlewareHandler($route, $this->container, $this);

        return $handler->handle($serverRequest);
    }

    public function callRouteAction(Route $route): AbstractResponse
    {
        /** @var callable|string $action A callable or an "at" string : "Controller@method" */
        $action = $route->getAction();

        $parameters = [];
        if (! \is_callable($action)) {
            // "Controller@method"
            /** @var class-string<object> $fqcn */
            [$fqcn, $method] = explode('@', $action, 2);
            $action = [$this->container->get($fqcn), $method];
            \assert(\is_callable($action));

            $routeSegments = $route->getActionArguments();

            $parameters = $this->buildRouteActionParameters($routeSegments, $fqcn, $method);
        }

        return $action(
            ...$parameters, // this unpacks an assoc array and make use of named arguments to inject the proper value taken from the URI segments to the correct argument
        );
    }

    /**
     * @param array<string, string> $routeSegments
     * @param class-string $controllerFqcn
     *
     * @return array<string, mixed>
     */
    private function buildRouteActionParameters(array $routeSegments, string $controllerFqcn, string $method): array
    {
        $parameters = [];
        $reflMethod = new ReflectionMethod($controllerFqcn, $method);

        $serverRequest = $this->container->get(ServerRequest::class);
        $queryStrings = $serverRequest->getQueryParams();

        $reflParameters = $reflMethod->getParameters();
        foreach ($reflParameters as $reflParameter) {
            $paramName = $reflParameter->getName();
            $paramIsOptional = $reflParameter->isOptional();

            $reflType = $reflParameter->getType();

            /** @var null|bool $typeAllowsNull */
            $typeAllowsNull = $reflType?->allowsNull();
            $paramIsMandatoryAndNonNullable = !$paramIsOptional && $typeAllowsNull === false;
            if (!$paramIsOptional && $typeAllowsNull === true) {
                $parameters[$paramName] = null;
            }

            /** @var null|bool $typeIsScalar */
            $typeIsScalar = null;
            /** @var null|class-string<object> $typeName */
            $typeName = null;

            $scalarCastFunction = fn (mixed $v): mixed => $v;
            if ($reflType instanceof ReflectionNamedType) {
                $typeIsScalar = $reflType->isBuiltin();
                $typeName = $reflType->getName();

                if ($typeIsScalar && $typeName !== 'string') {
                    $scalarCastFunction = $typeName . 'val'; // ie: intval();
                }
                \assert(\is_callable($scalarCastFunction));
            }

            $reflAttributes = $reflParameter->getAttributes();
            if ($reflAttributes === []) {
                if (isset($routeSegments[$paramName])) { // inject route segment
                    $parameters[$paramName] = $scalarCastFunction($routeSegments[$paramName]);
                } elseif ($typeIsScalar === false && $typeName !== null) { // inject from container if class
                    $parameters[$paramName] = $this->container->get($typeName); // @phpstan-ignore-line
                } elseif ($paramIsMandatoryAndNonNullable) {
                    throw new \UnexpectedValueException("Can't call method $method from controller $controllerFqcn, because we don't know what to do with argument $paramName");
                }

                continue;
            }

            foreach ($reflAttributes as $reflAttribute) {
                $attrName = $reflAttribute->getName();
                $attrArguments = $reflAttribute->getArguments();

                if ($attrName === AutowireParameter::class) {
                    $parameters[$paramName] = $this->container->getParameter($attrArguments[0]);

                    continue;
                }

                if ($attrName === MapRouteParameter::class) {
                    $segmentName = $attrArguments[0] ?? $paramName;
                    if (isset($routeSegments[$segmentName])) {
                        $parameters[$paramName] = $scalarCastFunction($routeSegments[$segmentName]);
                    } elseif ($paramIsMandatoryAndNonNullable) {
                        throw new \UnexpectedValueException("Unknown route segment '$segmentName', for param $paramName of method $controllerFqcn::$method.");
                    }

                    continue;
                }

                if ($attrName === MapQueryString::class) {
                    if ($typeIsScalar === true) {
                        $queryStringName = $attrArguments[0] ?? $paramName;
                        if (isset($queryStrings[$queryStringName])) {
                            $parameters[$paramName] = $scalarCastFunction($queryStrings[$queryStringName]);
                        } elseif ($paramIsMandatoryAndNonNullable) {
                            throw new \UnexpectedValueException("Unknown query string '$queryStringName', for param $paramName of method $controllerFqcn::$method.");
                        }

                        continue;
                    }

                    // hydrate DTO
                    \assert(\is_string($typeName));
                    \assert(class_exists($typeName));
                    $parameters[$paramName] = (new EntityHydrator())->hydrateOne($queryStrings, $typeName);

                    $validate = $this->getAttributeConstructorArgumentValue($reflAttribute, 'validate');
                    if ($validate === true) {
                        $validator = new Validator();
                        $validator
                            ->setData($parameters[$paramName])
                            ->validate()
                            ->throwIfNotValid();
                    }

                    continue;
                }
            }
        }

        return $parameters;
    }

    /**
     * @param ReflectionAttribute<object> $reflAttribute
     */
    private function getAttributeConstructorArgumentValue(ReflectionAttribute $reflAttribute, string $argumentName): mixed
    {
        // the array is index based and/or associative depending on if the arguments have been passed positional or named
        $arguments = $reflAttribute->getArguments();
        if (isset($arguments[$argumentName])) {
            return $arguments[$argumentName];
        }

        $reflMethod = new ReflectionMethod($reflAttribute->newInstance(), '__construct');
        foreach ($reflMethod->getParameters() as $i => $reflParameter) {
            if ($reflParameter->getName() !== $argumentName) {
                continue;
            }

            if (isset($arguments[$i])) {
                return $arguments[$i];
            }

            if ($reflParameter->isDefaultValueAvailable()) {
                return $reflParameter->getDefaultValue();
            }
        }

        $attrClass = $reflAttribute->getName();
        throw new \UnexpectedValueException("Can't resolve the value of the argument $argumentName for attribute $attrClass.");
    }

    public function sendResponse(AbstractResponse $response): void
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
