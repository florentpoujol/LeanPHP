<?php declare(strict_types=1);

namespace LeanPHP\Console\InputOutput;

abstract class AbstractOutput
{
    // TODO move colors in an enum
    public const string COLOR_DEFAULT = 'default';
    public const string COLOR_RED = 'red';
    public const string COLOR_GREEN = 'green';
    public const string COLOR_YELLOW = 'yellow';
    public const string COLOR_BLUE = 'blue';
    public const string COLOR_MAGENTA = 'magenta';
    public const string COLOR_CYAN = 'cyan';
    public const string COLOR_GRAY = 'gray';

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
            $bgColor = $this->colors[$bgColor] ?? '';

            if ($bgColor !== '') {
                $bgColor = "4$bgColor";
            }

            $color = $bgColor;
        }

        if ($textColor !== null) {
            $textColor = $this->colors[$textColor] ?? '';

            if ($textColor !== '') {
                $textColor = "3$textColor";
            }

            if ($color !== '' && $textColor !== '') {
                $color .= ';';
            }

            $color .= $textColor;
        }

        if ($color === '') {
            return $value;
        }

        return "\033[{$color}m" . $value . "\033[m";
    }

    public function write(string $value, string $bgColor = null, string $textColor = null): void
    {
        if ($bgColor !== null || $textColor !== null) {
            $value = $this->getColoredText($value, $bgColor, $textColor);
        }

        $this->writeOutput($value);
    }

    abstract protected function writeOutput(string $value): void;

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
        $this->writeOutput($this->renderTable($headers, $rows, $colSeparator));
    }
}