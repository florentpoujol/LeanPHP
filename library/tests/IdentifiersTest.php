<?php declare(strict_types=1);

namespace Tests\LeanPHP;

use LeanPHP\Identifiers\RandomString;
use LeanPHP\Identifiers\TimeBased16;
use LeanPHP\Identifiers\TimeBased8;
use LeanPHP\Identifiers\UUIDv1;
use LeanPHP\Identifiers\UUIDv4;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class IdentifiersTest extends TestCase
{
    // TODO actually compare with Ramsey or Symfony implementations

    public function test_uuid_v1(): void
    {
        $uuid = UUIDv1::make();

        self::assertSame(32, \strlen($uuid->getHex()));
        self::assertSame(36, \strlen($uuid->getUuid()));

        $uuidRegex = '/[a-f0-9]{8}-[a-f0-9]{4}-1[a-f0-9]{3}-[a-f0-9]{4}-[a-f0-9]{12}/';
        self::assertSame(1, preg_match($uuidRegex, $uuid->getUuid()));

        $uuid2 = UUIDv1::fromString($uuid->getHex());
        self::assertSame($uuid->getHex(), $uuid2->getHex());
        self::assertSame($uuid->getUuid(), $uuid2->getUuid());

        $uuid3 = UUIDv1::fromString($uuid->getUuid());
        self::assertSame($uuid->getHex(), $uuid3->getHex());
        self::assertSame($uuid->getUuid(), $uuid3->getUuid());
    }

    public function test_uuid_v1_time_low_and_clock_changes(): void
    {
        $uuid = UUIDv1::make();
        $uuid2 = UUIDv1::make();
        $uuid3 = UUIDv1::make();

        self::assertNotSame(substr($uuid->getHex(), 0, 8), substr($uuid2->getHex(), 0, 8)); // time low
        self::assertSame(substr($uuid->getHex(), 8, 8), substr($uuid2->getHex(), 8, 8)); // time mid and high
        self::assertNotSame(substr($uuid->getHex(), 16, 4), substr($uuid2->getHex(), 16, 4)); // clock
        self::assertSame(substr($uuid->getHex(), -12), substr($uuid2->getHex(), -12)); // node

        self::assertNotSame(substr($uuid3->getHex(), 0, 8), substr($uuid2->getHex(), 0, 8));
        self::assertSame(substr($uuid3->getHex(), 8, 8), substr($uuid2->getHex(), 8, 8));
        self::assertNotSame(substr($uuid3->getHex(), 16, 4), substr($uuid2->getHex(), 16, 4));
        self::assertSame(substr($uuid3->getHex(), -12), substr($uuid2->getHex(), -12)); // node
    }

    public function test_uuid_v1_same_mac_address_node(): void
    {
        $uuid = UUIDv1::make();
        $uuid2 = UUIDv1::make();
        $uuid3 = UUIDv1::make();

        self::assertSame(substr($uuid->getHex(), -12), substr($uuid2->getHex(), -12));
        self::assertSame(substr($uuid3->getHex(), -12), substr($uuid2->getHex(), -12));
    }

    public function test_uuid_v1_same_callback_node(): void
    {
        $callCount = 0;
        UUIDv1::setNodeProvider(function () use (&$callCount): string {
            ++$callCount;

            return '123456789abc';
        });

        $uuid = UUIDv1::make();
        self::assertSame('123456789abc', substr($uuid->getHex(), -12));
        $uuid2 = UUIDv1::make();
        self::assertSame('123456789abc', substr($uuid2->getHex(), -12));
        $uuid3 = UUIDv1::make();
        self::assertSame('123456789abc', substr($uuid3->getHex(), -12));

        self::assertSame(1, $callCount);
    }

    public function test_uuid_v4(): void
    {
        $uuid = UUIDv4::make();

        self::assertSame(32, \strlen($uuid->getHex()));
        self::assertSame(36, \strlen($uuid->getUuid()));

        $uuidRegex = '/[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[a-f0-9]{4}-[a-f0-9]{12}/';
        self::assertTrue(preg_match($uuidRegex, $uuid->getUuid()) === 1);

        $uuid2 = UUIDv4::fromString($uuid->getHex());
        self::assertSame($uuid->getHex(), $uuid2->getHex());
        self::assertSame($uuid->getUuid(), $uuid2->getUuid());

        $uuid3 = UUIDv4::fromString($uuid->getUuid());
        self::assertSame($uuid->getHex(), $uuid3->getHex());
        self::assertSame($uuid->getUuid(), $uuid3->getUuid());
    }

    public function test_basic_time_based_8(): void
    {
        $id = TimeBased8::make();

        self::assertSame(16, \strlen($id->getHex()), "hex: '" . $id->getHex() . "' bin:'" . $id->getRaw() . "'");
        self::assertTrue($id->getInteger() > microtime(true) * 100_000_000);

        $id2 = TimeBased8::fromString($id->getHex());
        self::assertSame($id->getHex(), $id2->getHex());
        self::assertSame($id->getInteger(), $id2->getInteger());

        $id3 = TimeBased8::fromInteger($id->getInteger());
        self::assertSame($id->getHex(), $id3->getHex());
        self::assertSame($id->getInteger(), $id3->getInteger());

        $this->expectException(UnexpectedValueException::class);
        $id->getUuid();
    }

    public function test_basic_time_based_16(): void
    {
        $id = TimeBased16::make();

        self::assertSame(32, \strlen($id->getHex()));
        self::assertSame(36, \strlen($id->getUuid()));

        $id2 = TimeBased16::fromString($id->getHex());
        self::assertSame($id->getHex(), $id2->getHex());
    }

    public function test_random_string(): void
    {
        for ($i = 1; $i < 999; $i += 3) {
            $string = new RandomString($i);
            self::assertSame($i, \strlen($string->getHex()));
            self::assertSame($i, \strlen((string) $string));
        }
    }
}
