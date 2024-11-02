<?php declare(strict_types=1);

namespace LeanPHP\Console\InputOutput;

final class ArgvInput extends AbstractInput
{
    /**
     * @var array<string>
     */
    private array $tokens;

    /**
     * @param array<string> $argv
     */
    public function __construct(
        array $argv,
    ) {
        parent::__construct([], []);

        $this->tokens = $argv;
        array_shift($this->tokens);

        $this->parse();
    }

    private function parse(): void
    {
        foreach ($this->tokens as $token) {
            if ($token === '--') {
                // these checks are in Symfony's ArgvInput parseToken() method
                // but I'm not sure when they would exist
                continue;
            }

            if ($token === '-') {
                // these checks are in Symfony's ArgvInput parseToken() method
                // but I'm not sure when they would exist
                continue;
            }

            if (str_starts_with($token, '--')) {
                $this->parseLongOption($token);
            } elseif ($token[0] === '-') {
                $this->parseShortOption($token);
            } else {
                $this->arguments[] = $token;
            }
        }
    }

    private function parseShortOption(string $option): void
    {
        $option = substr($option, 1);

        if (\strlen($option) === 1) {
            $this->options[$option] = null;
        } else {
            // the first char is the option name, the rest is the value
            $this->options[$option[0]] = substr($option, 1);
        }
    }

    private function parseLongOption(string $option): void
    {
        $option = substr($option, 2);

        [$name, $value] = explode('=', $option, 2);

        // an array option
        if (isset($this->options[$name])) {
            if (!\is_array($this->options[$name])) {
                $this->options[$name] = [$this->options[$name]];
            }
            $this->options[$name][] = $value;

            return;
        }

        $this->options[$name] = $value;
    }
}
