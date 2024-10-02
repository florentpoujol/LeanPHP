<?php declare(strict_types=1);

namespace LeanPHP\Console\InputOutput;

final class EchoOutput extends AbstractOutput
{
    protected function writeOutput(string $value): void
    {
        echo $value . \PHP_EOL;
    }
}