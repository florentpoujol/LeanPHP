<?php declare(strict_types=1);

namespace LeanPHP\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

final class StackLogger extends AbstractLogger implements ConfigurableLogger
{
    use ConfigureLogger;

    public function __construct(
        /**
         * @var array<LoggerInterface> $loggers
         */
        public readonly array $loggers,
    ) {
    }

    /**
     * @param \Stringable|string $level
     * @param array<mixed> $context
     */
    public function log(mixed $level, \Stringable|string $message, array $context = []): void
    {
        $stringLevel = (string) $level;

        foreach ($this->loggers as $logger) {
            if (! ($logger instanceof ConfigurableLogger)) {
                $logger->log($level, $message, $context);

                continue;
            }

            if (!$logger->handleLevel($stringLevel)) {
                continue;
            }

            $logger->log($level, $message, $context);

            if (!$logger->doHandleNextLoggerInStack()) {
                break;
            }
        }
    }
}
