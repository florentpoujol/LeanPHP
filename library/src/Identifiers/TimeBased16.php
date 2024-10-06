<?php declare(strict_types=1);

namespace LeanPHP\Identifiers;

/**
 * A 16 bytes time-based identifier that begins by the micro-timestamp on 7 bytes (enough for until the year 4253) and is followed by 9 random bytes.
 */
final class TimeBased16 extends Identifier
{
    protected function generate(): string
    {
        $time = gettimeofday();
        $hexTime = dechex((int) ($time['sec'] . $time['usec']));
        if (\strlen($hexTime) % 2 !== 0) {
            $hexTime = '0' . $hexTime;
        }
        // as of 2022, and until 2112, the hex version of the microtimestamp is 13 chars long, so we pad it here to 14

        return hex2bin($hexTime) . random_bytes(9);
    }
}
