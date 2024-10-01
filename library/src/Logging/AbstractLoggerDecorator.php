<?php declare(strict_types=1);

namespace LeanPHP\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

abstract class AbstractLoggerDecorator extends AbstractLogger
{
    public function __construct(
        public readonly LoggerInterface $decoratedLogger,
    ) {
    }

    /**
     * @param \Stringable|string $level
     * @param array<mixed> $context
     */
    public function log(mixed $level, \Stringable|string $message, array $context = []): void
    {
        $this->decoratedLogger->log($level, $message, $context);
    }
}
