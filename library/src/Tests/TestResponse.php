<?php declare(strict_types=1);

namespace LeanPHP\Tests;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

final readonly class TestResponse
{
    public function __construct(
        public ResponseInterface $response,
    ) {
    }

    public function assertStatus(int $status): void
    {
        Assert::assertSame($status, $this->response->getStatusCode());
    }

    public function getContent(): string
    {
        return $this->response->getBody()->getContents();
    }
}