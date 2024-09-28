<?php

declare(strict_types=1);

namespace LeanPHP\Http;

final class Router
{
    /**
     * @var array<string, array<string, array<Route>>> Routes instances by HTTP methods and prefixes
     */
    private array $routes = [
        // HTTP method => [
        //     /prefix => [
        //         route 1
        //         route 2
        //     ]
        // ]
    ];

    /**
     * @var array<string, Route>
     */
    private array $routesByName = [];


    /**
     * @param array<Route> $routes
     */
    public function __construct(array $routes) {
        $this->collectRoutes($routes);
    }

    /**
     * @param array<Route> $routes
     */
    private function collectRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            $this->routesByName[$route->getName()] = $route; // used for URL generation

            $uri = $route->getUri();
            $prefix = $uri;
            $lengthUpToPlaceholderNonIncluded = strpos($uri, '{');
            if (\is_int($lengthUpToPlaceholderNonIncluded)) {
                $prefix = substr($route->getUri(), 0, $lengthUpToPlaceholderNonIncluded);
            }

            foreach ($route->getMethods() as $method) {
                $this->routes[$method][$prefix][] = $route;
            }
        }

        foreach ($this->routes as $method => $routesByPrefix) {
            krsort($routesByPrefix); // sort alphabetically in reverse order, so that the longest prefixes are first
            $this->routes[$method] = $routesByPrefix;
        }
    }

    /**
     * @throws \UnexpectedValueException When no route with that name is found
     */
    public function getRouteByName(string $name): Route
    {
        if (isset($this->routesByName[$name])) {
            return $this->routesByName[$name];
        }

        throw new \UnexpectedValueException("Unknown route name '$name'.");
    }

    public function resolveRoute(string $method, string $uri): ?Route
    {
        if (! isset($this->routes[$method])) {
            // no routes
            return null;
        }

        $uri = '/' . trim($uri, ' /');

        foreach ($this->routes[$method] as $prefix => $routes) {
            if (! str_starts_with($uri, $prefix)) {
                continue;
            }

            // We found all routes which prefix match the current URI,
            // now we need to find which route actually match the whole URI.
            // Even if there is a single route, it does not mean it match.
            foreach ($routes as $route) {
                if ($route->match($uri)) {
                    return $route;
                }
            }
        }

        return null;
    }
}
