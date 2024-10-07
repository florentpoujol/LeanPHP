<?php declare(strict_types=1);

namespace LeanPHP\Tests;

use LeanPHP\Http\AbstractResponse;
use PHPUnit\Framework\Assert;

final readonly class TestResponse
{
    public function __construct(
        public AbstractResponse $response,
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