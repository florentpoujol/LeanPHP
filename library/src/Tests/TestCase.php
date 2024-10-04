<?php declare(strict_types=1);

namespace LeanPHP\Tests;

use LeanPHP\Container;
use LeanPHP\Http\HttpKernel;
use LeanPHP\Http\ServerRequest;
use Nyholm\Psr7\ServerRequest as PsrServerRequest;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initContainer();
    }

    protected function initContainer(): void
    {
        if (!\defined('APP_ENV_OVERRIDE')) {
            \define('APP_ENV_OVERRIDE', 'test');
        }

        require __DIR__ . '/../../../bootstrap/app.php';

        \assert(isset($container) && $container instanceof Container);

        $this->container = $container;
    }

    /**
     * @param array<string, string> $headers
     * @param array<mixed>|string $body
     */
    public function doRequest(string $method, string $uri, array $headers = [], null|array|string $body = null): TestResponse
    {
        // create server request
        // create kernel and pass the

        // note Florent 27/09/24: using FastRoute instead of the built-in slow/dumb router, but only if we do not care to insert the matched route object into the container.
        // Customizing FastRoute to allow that is basically not possible.
        // Eventually replace by the Symfony router, or at least do something similar to the Tempest router that also build a single regex (but in a way different from FastRoute).

        $httpKernel = new HttpKernel($this->container);

        if (\is_array($body)) {
            $body = json_encode($body, \JSON_THROW_ON_ERROR); // maybe only allow array body to the jsonRequest method
            \assert(\is_string($body));
        }

        $serverRequest = new ServerRequest(new PsrServerRequest($method, $uri, $headers, $body));

        $response = $httpKernel->handle(
            require __DIR__ . '/../../../bootstrap/routes.php',
            $serverRequest,
        );

        return new TestResponse($response);
    }

    /**
     * @param array<string, string> $headers
     * @param array<mixed>|string $body
     */
    public function doJsonRequest(string $method, string $uri, array $headers = [], null|array|string $body = null): TestResponse
    {
        $headers += [ // using += so that these defaults to do override already set headers
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        return  $this->doRequest($method, $uri, $headers, $body);
    }
}