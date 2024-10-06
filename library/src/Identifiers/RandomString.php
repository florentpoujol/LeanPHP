<?php declare(strict_types=1);

namespace LeanPHP\Identifiers;

use Stringable;
use UnexpectedValueException;

/**
 * A random string, that can be get as Hex, or a string that may contain lower and upper case letters as well as numbers.
 */
final class RandomString extends Identifier implements Stringable
{
    public function __construct(
        /**
         * @var positive-int $size
         */
        private int $size = 10,
    ) {
        if ($size < 1) {
            throw new UnexpectedValueException("The size ($size) must be at least 1.");
        }

        parent::__construct();
    }

    protected function generate(): string
    {
        $size = $this->size;

        do {
            $this->binary = random_bytes($size);
            $size += 1;
        } while (\strlen($this->getString()) < $this->size);
        // in base64, even when stripping the +, / and = chars,
        // the generated string will typically be longer than the number of bytes it was generated with
        // but with very short string

        return $this->binary;
    }

    public function getString(): string
    {
        $search = ['+', '/', '='];

        return substr(str_replace($search, '', base64_encode($this->binary)), 0, $this->size);
    }

    public function __toString(): string
    {
        return $this->getString();
    }

    public function getHex(): string
    {
        return substr(bin2hex($this->binary), 0, $this->size);
    }
}
