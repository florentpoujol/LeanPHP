<?php

declare(strict_types=1);

use LeanPHP\Http\HttpKernel;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;

require_once __DIR__ . '/../vendor/autoload.php';

// --------------------------------------------------
// Read environment
//
// Environment::readFileIfExists(__DIR__ . '/../.env');
// $environmentName = Environment::getStringOrDefault('APP_ENV', 'prod');
//
// $envNameRegex = '/^[a-z_-]{4,15}$/';
// if ((int)preg_match($envNameRegex, $environmentName) === 0) {
//     throw new Exception("The environment name '$environmentName' doesn't respect the regex '$envNameRegex'. Check the value of the APP_ENV variable.");
// }
//
// Environment::readFileIfExists(__DIR__ . "/../.env.$environmentName");
// Environment::readFileIfExists(__DIR__ . "/../.env.$environmentName.local");

// Alternatively use https://github.com/vlucas/phpdotenv instead of the built-in env file reader:
// Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env')->safeload()

// --------------------------------------------------
// Load bootstrap

// $bootstrap = new Container();
// $bootstrap->setInstance(Container::class, $bootstrap);
// $bootstrap->setParameter('environmentName', $environmentName);
// require_once __DIR__ . '/../bootstrap/factories.php'; // fill the containe

// --------------------------------------------------


$httpKernel = new HttpKernel(
    // $container
);

$psr17Factory = new Psr17Factory();
$serverRequestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

$serverRequest = $serverRequestCreator->fromGlobals();
assert($serverRequest instanceof ServerRequest);
// $serverRequestInterface = $container->get(ServerRequestInterface::class);

// note Florent 27/09/24: using FastRoute instead of the built-in slow/dumb router, but only if we do not care to insert the matched route object into the container.
// Customizing FastRoute to allow that is basically not possible.
// Eventually replace by the Symfony router, or at least do something similar to the Tempest router that also build a single regex (but in a way different from FastRoute).

$response = $httpKernel->handle(
    require_once __DIR__ . '/../bootstrap/routes.php',
    $serverRequest,
);

$httpKernel->sendResponse($response);
