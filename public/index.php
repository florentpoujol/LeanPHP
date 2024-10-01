<?php

use LeanPHP\Container;
use LeanPHP\Http\HttpKernel;
use LeanPHP\Http\ServerRequest;

require_once __DIR__ . '/../bootstrap/app.php';

assert(isset($container) && $container instanceof Container);

// note Florent 27/09/24: using FastRoute instead of the built-in slow/dumb router, but only if we do not care to insert the matched route object into the container.
// Customizing FastRoute to allow that is basically not possible.
// Eventually replace by the Symfony router, or at least do something similar to the Tempest router that also build a single regex (but in a way different from FastRoute).

$httpKernel = new HttpKernel($container);

$response = $httpKernel->handle(
    require_once __DIR__ . '/../bootstrap/routes.php',
    $container->get(ServerRequest::class),
);

$httpKernel->sendResponse($response);