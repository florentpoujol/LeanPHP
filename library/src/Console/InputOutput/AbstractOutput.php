<?php declare(strict_types=1);

namespace LeanPHP\Console\InputOutput;

abstract class AbstractOutput
{
    /**
     * @param array<TerminalDisplayCode> $displayCodes
     */
    public function write(string $value, array $displayCodes = []): void
    {
        $value = TerminalDisplayCode::getDecoratedString($value, $displayCodes);

        $this->writeOutput($value);
    }

    abstract protected function writeOutput(string $value): void;

    public function writeSuccess(string $value): void
    {
        $this->write($value, [TerminalDisplayCode::BG_GREEN]);
    }

    public function writeError(string $value): void
    {
        $this->write($value, [TerminalDisplayCode::BG_RED]);
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
                $headers[$id] = TerminalDisplayCode::getDecoratedString($header, [TerminalDisplayCode::BOLD]);
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