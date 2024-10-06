<?php

declare(strict_types=1);

namespace LeanPHP\Identifiers;

use UnexpectedValueException;

final class UUIDv1 extends Identifier
{
    protected function generate(): string
    {
        $time = gettimeofday();
        $time = (int) ($time['sec'] . $time['usec'] . '0'); // note here that we have a number precise to 1/10th of a microsecond

        $hexTime = dechex($time + 122_192_928_000_000_000);
        // 122_192_928_000_000_000 is the number of 100-ns intervals between the
        // UUID epoch 1582-10-15 00:00:00 and the Unix epoch 1970-01-01 00:00:00.
        // https://github.com/symfony/polyfill-uuid/blob/main/Uuid.php

        // as of February 2022 $hexTime is already 15 hex chars

        $bin = hex2bin(
            substr($hexTime, 7, 8) . // time low
            substr($hexTime, 3, 4) . // time mid
            '1' . substr($hexTime, 0, 3) . // version and time hi

            bin2hex(random_bytes(2)) . // clock sequence

            self::getNode(), // 6 bytes for the node
        );
        \assert(\is_string($bin));

        return $bin;
    }

    /**
     * @var string A 12 digits hexadecimal string
     */
    private static string $node = '';

    public static function setNode(?string $node): void
    {
        if ($node === null) {
            self::$node = '';

            return;
        }

        if (\strlen($node) !== 12) {
            throw new UnexpectedValueException("The provided node ($node) is expected to be 12 hexadecimal characters.");
        }

        self::$node = $node;
    }

    /**
     * @var null|callable
     */
    private static $nodeProvider;

    public static function setNodeProvider(?callable $provider): void
    {
        self::$node = '';
        self::$nodeProvider = $provider;
    }

    public static function getNode(): string
    {
        if (self::$node !== '') {
            return self::$node;
        }

        if (self::$nodeProvider !== null) {
            self::$node = (self::$nodeProvider)();

            if (\strlen(self::$node) !== 12) {
                $node = self::$node;
                throw new UnexpectedValueException("The node provided from a callable ($node) is expected to be 12 hexadecimal characters.");
            }
        } else {
            self::$node = str_replace([':', '-'], '', self::getMacAddress());
        }

        if (self::$node === '') {
            self::$node = bin2hex(random_bytes(6));
        }

        return self::$node;
    }

    private static function getMacAddress(): string
    {
        /** @see https://github.com/ramsey/uuid/blob/main/src/Provider/Node/SystemNodeProvider.php */
        $command = match (strtoupper(substr(\PHP_OS, 0, 3))) {
            'WIN' => 'ipconfig /all', // Windows family
            'DAR' => 'ifconfig', // Darwin
            'FRE' => 'netstat -i -f link', // FreeBSD
            default => 'netstat -ie', // Linux and the rest.
            // note that netstat isn't installed by default on Ubuntu 21.10
        };

        $commandResult = shell_exec($command);
        if ($commandResult === null) {
            return '';
        }
        \assert(\is_string($commandResult));

        // pattern to match nodes in ifconfig and ipconfig output
        $macPattern = '/[^:]([0-9a-f]{2}([:-])[0-9a-f]{2}(\2[0-9a-f]{2}){4})[^:]/i';
        $matches = [];

        preg_match_all($macPattern, $commandResult, $matches);

        return $matches[1][0] ?? '';
    }
}
