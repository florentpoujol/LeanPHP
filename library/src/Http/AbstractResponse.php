<?php declare(strict_types=1);

namespace LeanPHP\Http;

use Nyholm\Psr7\Response as PsrResponse;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

abstract class AbstractResponse implements ResponseInterface
{
    public ResponseInterface $psrResponse;

    /**
     * @param array<string, string|array<string>> $headers
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        null|string $body = null,
    ) {
        $this->psrResponse = new PsrResponse($status, $headers, $body);
    }

    public function setCookie(
        string $name,
        string $value,
        bool $httpOnly = true,
        bool $secure = true,
        string $sameSite = 'Strict',
        string $other = '',
    ): void {
        $value = "$name=$value; SameSite=$sameSite;";
        $value .= $httpOnly ? ' HttpOnly;' : '';
        $value .= $secure ? ' Secure;' : '';
        $value .= $other;

        $this->psrResponse = $this->psrResponse
            ->withAddedHeader('Set-Cookie', $value);
    }

    public function deleteCookie(string $name): void
    {
        $value = "$name=a; Max-Age=1";

        $this->psrResponse = $this->psrResponse
            ->withAddedHeader('Set-Cookie', $value);
    }

    // --------------------------------------------------
    // methods from the interface

    public function getProtocolVersion(): string
    {
        return $this->psrResponse->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->psrResponse->withProtocolVersion($version);
    }

    public function getHeaders(): array
    {
        return $this->psrResponse->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->psrResponse->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->psrResponse->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->psrResponse->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->psrResponse->withHeader($name, $value);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->psrResponse->withAddedHeader($name, $value);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return $this->psrResponse->withoutHeader($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->psrResponse->getBody();
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->psrResponse->withBody($body);
    }

    public function getStatusCode(): int
    {
        return $this->psrResponse->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->psrResponse->withStatus($code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->psrResponse->getReasonPhrase();
    }
}