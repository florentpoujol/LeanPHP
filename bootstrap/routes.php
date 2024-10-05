<?php declare(strict_types=1);

use App\Http\AuthController;
use App\Http\PublicController;
use LeanPHP\Http\Route;
use Nyholm\Psr7\Response;

return [
    new Route(['get'], '/', PublicController::class . '@index'),
    new Route(['get'], '/posts/{id}', PublicController::class . '@index'),

    // auth
    new Route(['GET'], '/auth/login', AuthController::class . '@showLoginForm'),
    new Route(['POST'], '/auth/login', AuthController::class . '@login'),

    // must be the last route
    new Route(['GET', 'POST', 'PUT', 'HEAD', 'DELETE'], '/{any}', fn($any): \Nyholm\Psr7\Response => new Response(404, body: 'this is the fallback route'), ['any' => '.*']),
];