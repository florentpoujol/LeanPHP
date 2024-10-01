<?php declare(strict_types=1);

namespace LeanPHP\Logging;

use Exception;
use Psr\Log\AbstractLogger;
use Stringable;

final class ResourceLogger extends AbstractLogger implements ConfigurableLogger
{
    use ConfigureLogger;

    /**
     * @var callable(string $level, string $message, array $context = []): string
     */
    private array|string|object $formatter; // @phpstan-ignore-line (since we can't put callable as property type, array|string|object is the next best thing)
    public function __construct(
        private readonly string $logFilePath = '',
        /**
         * @var null|resource
         */
        private        $logFileResource = null,
        null|callable  $formatter = null, // this one isn't promoted so that we can put the callable type declaration, since we can't put it on properties
    ) {
        $this->formatter = $formatter ?? [$this, 'defaultLineFormatter']; // @phpstan-ignore-line (Property ... (array|object|string) does not accept callable) (yes it does in this case, dear)
    }

    private function openResource(): void
    {
        if ($this->logFilePath === 'STDOUT') {
            $this->logFileResource = \STDOUT;

            return;
        }

        if ($this->logFilePath === 'STDERR') {
            $this->logFileResource = \STDERR;

            return;
        }

        $resource = fopen($this->logFilePath, 'a+'); // a+ = reading and writing, pointer at EOF, create file if not exists
        if (! \is_resource($resource)) {
            throw new Exception("Can't create resource for path '$this->logFilePath'.");
        }

        $this->logFileResource = $resource;
    }

    /**
     * @param array<mixed> $context
     */
    private function defaultLineFormatter(string $level, string $message, array $context): string
    {
        $datetime = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        $line = "[$datetime] $level: $message";

        if ($context !== []) {
            $line .= ' ' . json_encode($context);
        }

        return $line;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|Stringable $level
     * @param array<string, mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $level = (string) $level;
        if (! $this->handleLevel($level)) {
            return;
        }

        if ($this->logFileResource === null) {
            $this->openResource();
        }
        \assert(\is_resource($this->logFileResource));

        $formatter = $this->formatter;
        \assert(\is_callable($formatter));
        $line = $formatter($level, (string) $message, $context);

        fwrite($this->logFileResource, $line . \PHP_EOL);
    }

    public function __destruct()
    {
        if ($this->logFileResource !== null) {
            fclose($this->logFileResource);
        }
    }
}
