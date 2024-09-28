<?php declare(strict_types=1);

use App\PublicController;
use Nyholm\Psr7\Response;
use LeanPHP\Http\Route;

return [
    new Route(['get'], '/', PublicController::class . '@index'),
    new Route(['get'], '/posts/{id}', PublicController::class . '@index'),

    new Route(['GET', 'POST', 'PUT', 'HEAD', 'DELETE'], '/{any}', fn($any): \Nyholm\Psr7\Response => new Response(404, body: "this is the fallback route"), ['any' => '.*']),
];