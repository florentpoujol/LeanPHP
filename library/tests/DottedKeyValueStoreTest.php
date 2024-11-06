<?php

declare(strict_types=1);

namespace Tests\LeanPHP;

use LeanPHP\Collection\DottedKeyValueStore;
use LeanPHP\Collection\ValueNotFoundException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class DottedKeyValueStoreTest extends TestCase
{
    public function test_set(): void
    {
        // arrange
        $store = new DottedKeyValueStore();

        // act
        $store->set('key', 'value1');
        $store->set('key.key', 'value.value1');
        $store->set('key.key.key', 'value.value.value1');

        $store->set('key2', 10);
        $store->set('key.key2', false);
        $store->set('key.key.key2', []);

        // assert
        $expectedData = [
            'key' => [
                'key' => [
                    'key' => 'value.value.value1',
                    'key2' => [],
                ],
                'key2' => false,
            ],
            'key2' => 10,
        ];

        self::assertSame($expectedData, $store->getAll());
    }

    public function test_unset(): void
    {
        // arrange
        $store = new DottedKeyValueStore([
            'key' => [
                'key' => [
                    'key' => 'value.value.value1',
                    'key2' => [],
                ],
                'key2' => false,
            ],
            'key2' => 10,
        ]);

        // act
        $store->unset('unknown');
        $store->unset('key2');

        $store->unset('key.key2');
        $store->unset('key.unknown');

        $store->unset('key.key.key2');
        $store->unset('key.key.unknown');

        // assert
        $expectedData = [
            'key' => [
                'key' => [
                    'key' => 'value.value.value1',
                ],
            ],
        ];

        self::assertSame($expectedData, $store->getAll());
    }

    public function test_unset_throws_exception(): void
    {
        // arrange
        $store = new DottedKeyValueStore([
            'key' => [
                'key' => 'scalar value',
            ],
        ]);

        // act & assert
        self::expectException(LogicException::class);
        self::expectExceptionMessage("Can't unset value for key 'key.key.key', because subset 'key.key' doesn't lead to an array but a value of type 'string'.");

        $store->unset('key.key.key');
    }

    public function test_get(): void
    {
        // arrange
        $store = new DottedKeyValueStore([
            'key' => [
                'key' => [
                    'key' => 'value.value.value1',
                    'key2' => [],
                ],
                'key2' => false,
            ],
            'key2' => 10,
        ]);

        // act & assert
        self::assertNull($store->get('unknown'));
        self::assertNull($store->get('unknown.unknown'));

        self::assertSame('default', $store->get('unknown', 'default'));
        self::assertSame('default', $store->get('unknown.unknown', 'default'));

        self::assertSame('value.value.value1', $store->get('key.key.key'));
        self::assertSame([], $store->get('key.key.key2'));

        self::assertSame([
            'key' => 'value.value.value1',
            'key2' => [],
        ], $store->get('key.key'));
        self::assertFalse($store->get('key.key2'));

        self::assertSame(10, $store->get('key2'));
    }

    public function test_get_int(): void
    {
        // arrange
        $store = new DottedKeyValueStore([
            'key' => '10',
        ]);

        // act & assert
        self::assertSame(10, $store->getInt('key'));
        self::assertSame(10, $store->getInt('key', 10));

        self::assertSame(11, $store->getInt('unknown', 11));
        self::assertSame(11, $store->getInt('unknown.unknown', 11));

        self::assertNull($store->getInt('unknown', null));
        self::assertNull($store->getInt('unknown.unknown', null));

        self::expectException(ValueNotFoundException::class);
        self::expectExceptionMessage("No value found for key 'unknown', and no default provided.");
        $store->getInt('unknown');
    }
}
