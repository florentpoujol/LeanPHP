<?php declare(strict_types=1);

use App\Http\AuthController;
use App\Http\PublicController;
use LeanPHP\Http\Response;
use LeanPHP\Http\Route;
use LeanPHP\Http\Session\RedirectIfAuthenticatedMiddleware;
use LeanPHP\Http\Session\SessionMiddleware;

$routes = [
    new Route(['get'], '/', PublicController::class . '@index'),
    new Route(['get'], '/posts/{id}', PublicController::class . '@index'),

    // auth
    (new Route(['GET'], '/auth/login', AuthController::class . '@showLoginForm'))
        ->setMiddleware([
            RedirectIfAuthenticatedMiddleware::class,
        ]),
    (new Route(['POST'], '/auth/login', AuthController::class . '@login'))
        ->setMiddleware([]),

    (new Route(['GET'], '/auth/logout', AuthController::class . '@logout')),

    // must be the last route
    new Route(['GET', 'POST', 'PUT', 'HEAD', 'DELETE'], '/{any}', fn($any): Response => new Response(404, body: 'this is the fallback route'), ['any' => '.*']),
];

/** @var Route $route */
foreach ($routes as $route) {
    $route->addMiddleware(SessionMiddleware::class);
}

return $routes;