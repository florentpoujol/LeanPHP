<?php declare(strict_types=1);

namespace LeanPHP\Logging;

use Psr\Log\LogLevel;

/**
 * @see ConfigureLogger The trait that implement this interface
 */
interface ConfigurableLogger
{
    /**
     * @param array<LogLevel::*> $levels
     */
    public function setHandledLevels(array $levels): static;

    /**
     * @param LogLevel::* $level
     */
    public function handleLevel(string $level): bool;

    public function setHandleNextLoggerInStack(bool $handle): static;

    public function doHandleNextLoggerInStack(): bool;
}