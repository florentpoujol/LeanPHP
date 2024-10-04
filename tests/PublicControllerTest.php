<?php declare(strict_types=1);

namespace Tests;

use LeanPHP\Tests\TestCase;

final class PublicControllerTest extends TestCase
{
    public function test_test(): void
    {
        $testResponse = $this->doRequest('GET', '/', []);

        $testResponse->assertStatus(200);

        $content = $testResponse->getContent();

        self::assertStringContainsString('hello', $content);
    }

    public function test_test_2(): void
    {
        $testResponse = $this->doRequest('GET', '/lmjmjklmlkmqsdfsqd');

        $testResponse->assertStatus(404);
    }
}
