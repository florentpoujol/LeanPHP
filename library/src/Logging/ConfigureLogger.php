<?php declare(strict_types=1);

namespace LeanPHP\Logging;

use Psr\Log\LogLevel;

/**
 * @see ConfigurableLogger The interface that this trait implements
 */
trait ConfigureLogger
{
    /**
     * @var array<LogLevel::*>
     */
    private array $handledLevels = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    /**
     * @param array<LogLevel::*> $levels
     */
    public function setHandledLevels(array $levels): static
    {
        $this->handledLevels = $levels;

        return $this;
    }

    /**
     * @param LogLevel::* $level
     */
    public function handleLevel(string $level): bool
    {
        return \in_array(strtolower($level), $this->handledLevels, true);
    }

    /**
     * @param LogLevel::* $level
     */
    public function setMinimumLevel(string $level): static
    {
        $allLevels = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
            LogLevel::ALERT,
            LogLevel::EMERGENCY,
        ];

        $offset = array_search($level, $allLevels, true);
        \assert(\is_int($offset));
        $this->handledLevels = \array_slice($allLevels, $offset);

        return $this;
    }

    // --------------------------------------------------

    private bool $handleNextLoggerInStack = true;

    public function setHandleNextLoggerInStack(bool $handle): static
    {
        $this->handleNextLoggerInStack = $handle;

        return $this;
    }

    public function doHandleNextLoggerInStack(): bool
    {
        return $this->handleNextLoggerInStack;
    }
}