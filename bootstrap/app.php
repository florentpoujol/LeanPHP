<?php

declare(strict_types=1);

use LeanPHP\Container;
use LeanPHP\Environment;
use LeanPHP\Http\HttpKernel;
use LeanPHP\Http\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

require_once __DIR__ . '/../vendor/autoload.php';

// --------------------------------------------------
// Read environment
//
Environment::readFileIfExists(__DIR__ . '/../.env');
$environmentName = Environment::getStringOrDefault('APP_ENV', 'production');

$envNameRegex = '/^[a-z_-]{3,20}$/';
if ((int)preg_match($envNameRegex, $environmentName) === 0) {
    throw new \Exception("The environment name '$environmentName' doesn't respect the regex '$envNameRegex'. Check the value of the APP_ENV variable.");
}

Environment::readFileIfExists(__DIR__ . "/../.env.$environmentName");
Environment::readFileIfExists(__DIR__ . "/../.env.$environmentName.local");

// Alternatively use https://github.com/vlucas/phpdotenv instead of the built-in env file reader:
// Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env')->safeload();

// --------------------------------------------------
// Load bootstrap

$container = new Container();
$container->setParameter('environmentName', $environmentName);

require_once __DIR__ . '/../bootstrap/factories.php'; // fill the container

// --------------------------------------------------

// note Florent 27/09/24: using FastRoute instead of the built-in slow/dumb router, but only if we do not care to insert the matched route object into the container.
// Customizing FastRoute to allow that is basically not possible.
// Eventually replace by the Symfony router, or at least do something similar to the Tempest router that also build a single regex (but in a way different from FastRoute).

$httpKernel = new HttpKernel($container);

$response = $httpKernel->handle(
    require_once __DIR__ . '/../bootstrap/routes.php',
    $container->get(ServerRequest::class),
);

$httpKernel->sendResponse($response);
