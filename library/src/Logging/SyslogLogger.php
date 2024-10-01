<?php declare(strict_types=1);

namespace LeanPHP\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

final class SyslogLogger extends AbstractLogger
{
    /**
     * @var callable(int|string $level, string $message, array $context = []): string
     */
    private array|string|object $formatter; // @phpstan-ignore-line (since we can't put callable as property type, array|string|object is the next best thing)
    public function __construct(
        private string $syslogPrefix = '',
        null|callable  $formatter = null, // this one isn't promoted so that we can put the callable type declaration, since we can't put it on properties
    ) {
        if ($this->syslogPrefix === '') {
            $this->syslogPrefix = 'leanphp-app-log';
        }

        $this->formatter = $formatter ?? [$this, 'defaultLineFormatter']; // @phpstan-ignore-line (Property ... (array|object|string) does not accept callable) (yes it does in this case, dear)
    }

    /**
     * @param array<mixed> $context
     */
    private function defaultLineFormatter(int|string $level, string $message, array $context): string
    {
        $datetime = date('Y-m-d H:i:s');

        $level = strtoupper(match ($level) {
            \LOG_EMERG => LogLevel::EMERGENCY,
            \LOG_ALERT => LogLevel::ALERT,
            \LOG_CRIT => LogLevel::CRITICAL,
            \LOG_ERR => LogLevel::ERROR,
            \LOG_WARNING => LogLevel::WARNING,
            \LOG_NOTICE => LogLevel::NOTICE,
            \LOG_INFO => LogLevel::INFO,
            \LOG_DEBUG => LogLevel::DEBUG,
            default => (string) $level,
        });

        $line = "[$datetime] $level: $message";

        if ($context !== []) {
            $line .= ' ' . json_encode($context);
        }

        return $line;
    }

    /**
     * {@inheritDoc}
     *
     * @param int|string|Stringable $level
     * @param array<string, mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $facility = \LOG_LOCAL0;
        if (strtoupper(substr(\PHP_OS, 0, 3)) === 'WIN') {
            $facility = \LOG_USER;
        }

        openlog($this->syslogPrefix, \LOG_PID, $facility);

        if (!\is_int($level)) {
            $level = strtolower((string) $level);
        }

        $formatter = $this->formatter;
        \assert(\is_callable($formatter));

        $line = $formatter($level, (string) $message, $context);

        syslog($this->convertLevel($level), $line);

        closelog(); // TODO check if openlog() closelog() should be done only once
    }

    private function convertLevel(int|string $level): int
    {
        if (\is_int($level)) {
            return $level;
        }
        
        return match ($level) {
            LogLevel::EMERGENCY => \LOG_EMERG,
            LogLevel::ALERT => \LOG_ALERT,
            LogLevel::CRITICAL => \LOG_CRIT,
            LogLevel::ERROR => \LOG_ERR,
            LogLevel::WARNING => \LOG_WARNING,
            LogLevel::NOTICE => \LOG_NOTICE,
            LogLevel::DEBUG => \LOG_DEBUG,
            default => \LOG_INFO,
        };
    }
}
