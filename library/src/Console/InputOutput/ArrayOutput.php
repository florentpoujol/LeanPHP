<?php declare(strict_types=1);

namespace LeanPHP\Console\InputOutput;

final class ArrayOutput extends AbstractOutput
{
    /**
     * @var array<string> $output
     */
    private array $output = [];

    /**
     * @return array<string>
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    protected function writeOutput(string $value): void
    {
        $this->output[] = $value;
    }
}