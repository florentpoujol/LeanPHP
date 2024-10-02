<?php declare(strict_types=1);

namespace LeanPHP\Console;

abstract class AbstractCommand
{
    /**
     * @var array<string> Raw argument list. Original $argv argument passed to init() or $_SERVER['argv'].
     */
    protected array $argv = [];

    /**
     * @var array<string> $arguments
     */
    protected array $arguments = [];

    /**
     * @var array<string, string|null> $options
     */
    protected array $options = []; // key = name, value = value or null

    /**
     * @var array<string, mixed>
     */
    public array $config = [
        'name' => 'Base Command',
        'description' => 'A base command, to be extended.',

        'usage' => 'Not much to do with it in the cmd line, please extends the class to create your own console application.',
    ];

    /**
     * @param array<string>|null $argv
     */
    public function __construct(null|array $argv = null)
    {
        $argv ??= $_SERVER['argv'];
        $this->argv = $argv;

        array_shift($argv); // remove the file name which is always the first argument

        // build the argument and option lists
        foreach ($argv as $arg) {
            if ($arg[0] !== '-') {
                $this->arguments[] = $arg;

                continue;
            }

            // an option
            $parts = explode('=', $arg);
            $parts[0] = str_replace('-', '', $parts[0]);
            $this->options[$parts[0]] = $parts[1] ?? null;
            // We cannot get options values here when they are separated by a space
            // since they may be actual arguments
            // unless we know that the preceding option expect a mandatory value.
            // Same where the value is appended without = sign
        }

        // create the arrays if they don't so that we don't have to check if they exists everytime we want to use them
        $this->config['options'] ??= [];
        $this->config['optionAliases'] ??= [];
        $this->config['argumentNames'] ??= [];
    }

    /**
     * @return array<string>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name): ?string
    {
        \assert(\is_array($this->config['argumentNames']));
        $id = array_search($name, $this->config['argumentNames'], true);
        if ($id !== false) {
            return $this->arguments[$id] ?? null;
        }

        return null;
    }

    /**
     * @return array<string, string|null>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, null|string $defaultValue = null): ?string
    {
        $value = $this->options[$name] ?? $defaultValue;

        if ($value === null) {
            // option name not in the options array, or with null value
            // and default value is null, so suppose the option has a mandatory value
            // value is not already in the options array if the option value is appended to the option name or separated by a space
            // (not testable in a non-cli context)
            $queryName = "$name:";
            $longOpt = [];
            if (\strlen($name) >= 2) {
                $longOpt = [$queryName];
                $queryName = '';
            }
            $value = getopt($queryName, $longOpt);
            \assert(\is_array($value));
            $value = $value[$name] ?? $defaultValue;
        }

        \assert(\is_string($value) || $value === null);

        return $value;
    }

    public function hasOption(string $name): bool
    {
        $hasOption = \array_key_exists($name, $this->options) !== false;
        // do not use isset() here since it would return false
        // for keys existing in option but with the null value

        if ($hasOption === false) {
            // options which value is appended to the name are not in the options array
            // not has their actual name, but as the name+value appended
            // so look for it with getopt() for a more precise search
            // (not testable in a non-cli context)
            $queryName = "$name:";
            $longOpt = [];
            if (\strlen($name) >= 2) {
                $longOpt = [$queryName];
                $queryName = '';
            }
            $value = getopt($queryName, $longOpt);
            $hasOption = isset($value[$name]);
        }

        return $hasOption;
    }

    const string COLOR_DEFAULT = 'default';
    const string COLOR_RED = 'red';
    const string COLOR_GREEN = 'green';
    const string COLOR_YELLOW = 'yellow';
    const string COLOR_BLUE = 'blue';
    const string COLOR_MAGENTA = 'magenta';
    const string COLOR_CYAN = 'cyan';
    const string COLOR_GRAY = 'gray';

    /**
     * @var array<string, numeric-string>
     */
    protected array $colors = [
        'default' => '0',
        'red' => '1',
        'green' => '2',
        'brown' => '3',
        'blue' => '4',
        'magenta' => '5',
        'cyan' => '6',
        'gray' => '7',
        'other1' => '8',
    ];

    protected function getColoredText(string $value, string $bgColor = null, string $textColor = null): string
    {
        $color = '';
        if ($bgColor !== null) {
            if (!is_numeric($bgColor)) {
                $bgColor = $this->colors[$bgColor] ?? '';
            }
            if ($bgColor !== '') {
                $bgColor = "4$bgColor";
            }
            $color = $bgColor;
        }

        if ($textColor !== null) {
            if (!is_numeric($textColor)) {
                $textColor = $this->colors[$textColor] ?? '';
            }
            if ($textColor !== '') {
                $textColor = "3$textColor";
            }
            if ($color !== '' && $textColor !== '') {
                $color .= ';';
            }
            $color .= $textColor;
        }

        if ($color !== '') {
            return "\033[{$color}m" . $value . "\033[m";
        }

        return $value;
    }

    public function write(string $value, string $bgColor = null, string $textColor = null): void
    {
        if ($bgColor !== null || $textColor !== null) {
            $value = $this->getColoredText($value, $bgColor, $textColor);
        }

        echo $value . "\n";
    }

    public function writeSuccess(string $value): void
    {
        $this->write($value, self::COLOR_GREEN);
    }

    public function writeError(string $value): void
    {
        $this->write($value, self::COLOR_RED);
    }

    /**
     * @param array<string> $headers
     * @param array<array<string>> $rows
     */
    public function renderTable(array $headers, array $rows, string $colSeparator = ''): string
    {
        $colWidths = [];
        foreach ($headers as $id => $header) {
            if ($id !== 0 && $colSeparator !== '') {
                $header = $colSeparator . $header;
                $headers[$id] = $header;
            }
            $colWidths[] = \strlen($header);
        }
        $colWidths[\count($colWidths) - 1] = -1; // no restrictions on last column

        $table = implode('', $headers) . "\n";

        foreach ($rows as $row) {
            foreach ($row as $colId => $cell) {
                if ($colId !== 0 && $colSeparator !== '') {
                    $cell = $colSeparator . $cell;
                }

                $targetWidth = $colWidths[$colId];
                if ($targetWidth !== -1) {
                    $cellWidth = \strlen($cell);
                    if ($cellWidth === $targetWidth) {
                        continue;
                    }

                    $cell = str_pad($cell, $targetWidth);
                    if ($cellWidth > $targetWidth) {
                        $cell = substr($cell, 0, $targetWidth);
                    }
                }
                $row[$colId] = $cell;
            }

            $table .= implode('', $row) . "\n";
        }

        return $table;
    }

    /**
     * @param array<string> $headers
     * @param array<array<string>> $rows
     */
    public function writeTable(array $headers, array $rows, string $colSeparator = ''): void
    {
        echo $this->renderTable($headers, $rows, $colSeparator);
    }

    public function getHelpText(): string
    {
        $help = '';
        if (isset($this->config['usage'])) {
            $usage = $this->config['usage'];
            \assert(\is_string($usage));
            if (substr($usage, 0, 6) !== 'Usage:') {
                $usage = "Usage: $usage";
            }

            $help .= $usage . "\n";
        }

        if (isset($this->config['options'])) {
            $headers = ['    ', '          ', ''];
            \assert(\is_array($this->config['options']));
            $help .= $this->renderTable($headers, $this->config['options']);
        }

        return $help;
    }

    public function prompt(string $msg = ''): string
    {
        if ($msg !== '') {
            echo $msg . "\n";
        }

        $returned = fgets(\STDIN);
        \assert(\is_string($returned));

        return rtrim($returned, "\n");
    }

    public function promptConfirm(string $msg = 'Confirm ? [y/n]'): bool
    {
        if ($msg !== '') {
            echo $msg . "\n";
        }

        $returned = fgets(\STDIN);
        \assert(\is_string($returned));

        return strtolower($returned[0]) === 'y';
    }

    public function promptPassword(string $msg = 'Password:'): string
    {
        if ($msg !== '') {
            echo $msg . "\n";
        }

        system('stty -echo'); // will probably not work on windows
        $returned = fgets(\STDIN);
        \assert(\is_string($returned));
        $returned = rtrim($returned, "\n");
        system('stty echo');

        return $returned;
    }
}