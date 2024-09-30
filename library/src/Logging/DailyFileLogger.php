<?php declare(strict_types=1);

namespace LeanPHP\Logging;

use Psr\Log\AbstractLogger;
use Stringable;

final class DailyFileLogger extends AbstractLogger
{
    private string $currentLoggerDate = '';
    private ResourceLogger $decoratedLogger;

    public function __construct(
        private readonly string $logFolderPath,
    ) {
    }

    private function getLogger(): ResourceLogger
    {
        $currentDate = date('Y-m-d');
        if ($currentDate !== $this->currentLoggerDate) {
            $this->currentLoggerDate = $currentDate;
            $this->decoratedLogger = new ResourceLogger(logFilePath: "$this->logFolderPath/log-$currentDate.log");
        }

        return $this->decoratedLogger;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|Stringable $level
     * @param array<string, mixed> $context
     */
    // @phpstan-ignore-next-line (complain about the $level and $context argument not being contravariant because of the added PHPDoc)
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->getLogger()->log($level, $message, $context);
    }
}
