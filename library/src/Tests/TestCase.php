<?php declare(strict_types=1);

namespace LeanPHP\Tests;

use LeanPHP\Container\Container;
use LeanPHP\EntityHydrator\EntityHydratorInterface;
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
        require BASE_APP_PATH . '/bootstrap/init-container.php';

        require BASE_APP_PATH . '/bootstrap/init-session.php';

        \assert(isset($container) && $container instanceof Container);

        $this->container = $container;
    }

    /**
     * @param array<string, string> $headers
     * @param array<mixed>|string $body
     */
    public function doRequest(string $method, string $uri, array $headers = [], null|array|string $body = null): TestResponse
    {
        $httpKernel = new HttpKernel($this->container);

        if (\is_array($body)) {
            $body = json_encode($body, \JSON_THROW_ON_ERROR); // maybe only allow array body to the jsonRequest method
            \assert(\is_string($body));
        }

        $serverRequest = new ServerRequest(
            new PsrServerRequest($method, $uri, $headers, $body),
            $this->container->get(EntityHydratorInterface::class),
        );

        $response = $httpKernel->handle(
            require BASE_APP_PATH . '/bootstrap/routes.php',
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