<?php declare(strict_types=1);

namespace App\Http;

use LeanPHP\PhpViewRenderer;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final readonly class ExceptionHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environmentName,
        private PhpViewRenderer $viewRenderer,
    ) {
    }

    public function report(\Throwable $exception): void
    {
        $this->logger->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
        ]);
    }

    public function render(\Throwable $exception): ResponseInterface
    {
        if ($this->environmentName === 'dev') {
            $html = $this->viewRenderer->render('exceptions', [
                'exception' => $exception,
            ]);

            return new Response(500, body: $html);
        }

        return new Response(500, body: "There has been an error.");
    }
}