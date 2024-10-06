<?php declare(strict_types=1);

namespace LeanPHP\Http;

use LeanPHP\EntityHydrator\EntityHydratorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest
{
    public function __construct(
        public readonly ServerRequestInterface $psrRequest,
        private readonly EntityHydratorInterface $hydrator,
    ) {
    }

    public function getMethod(): string
    {
        return strtoupper($this->psrRequest->getMethod());
    }

    public function getUri(): UriInterface
    {
        return $this->psrRequest->getUri();
    }

    // --------------------------------------------------

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->psrRequest->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->psrRequest->hasHeader($name);
    }

    public function getHeader(string $name): string
    {
        return $this->psrRequest->getHeaderLine($name);
    }

    // --------------------------------------------------

    /**
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->psrRequest->getCookieParams();
    }

    public function getCookieOrThrow(string $name): string
    {
        $value = $this->psrRequest->getCookieParams()[$name] ?? null;
        if ($value === null) {
            throw new \UnexpectedValueException();
        }

        return $value;
    }

    public function getCookieOrNull(string $name): ?string
    {
        return $this->psrRequest->getCookieParams()[$name] ?? null;
    }

    public function getCookieOrDefault(string $name, string $default): string
    {
        return $this->psrRequest->getCookieParams()[$name] ?? $default;
    }

    // --------------------------------------------------

    /**
     * @return array<UploadedFileInterface>
     */
    public function getUploadedFiles(): array
    {
        return $this->psrRequest->getUploadedFiles();
    }

    public function getBody(): string
    {
        return $this->psrRequest->getBody()->getContents();
    }

    /**
     * @var array<mixed>|object|null
     */
    private null|array|object $parsedBody = null;

    /**
     * - When content is JSON, returns the body as an associative array.
     * - When content is XML and the SimpleXML extension is installed, return the body as a SimpleXMLElement.
     * - When request is POST and the body contains a form, returns the body in the same format a hte $_POST superglobal
     * - Otherwise is null, use the getBody() method to get the body as a string
     *
     * @return null|array<mixed>|object
     */
    public function getParsedBody(bool $jsonAsObject = false): null|array|object
    {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }

        $contentType = $this->getHeader('Content-Type')[0] ?? '';

        if ($contentType === 'application/json') {
            $array = json_decode($this->getBody(), !$jsonAsObject, 512, \JSON_THROW_ON_ERROR) ?? [];
            \assert(\is_array($array));
            $this->parsedBody = $array;

            return $this->parsedBody;
        }

        if (str_ends_with($contentType, 'xml') && class_exists(\SimpleXMLElement::class)) {
            return new \SimpleXMLElement($this->getBody());
        }

        // in the case of 'multipart/form-data' or 'application/x-www-form-urlencoded', the parsed body is already set to the value of $_POST
        /* @see \Nyholm\Psr7Server\ServerRequestCreator::fromGlobals */

        $this->parsedBody = $this->psrRequest->getParsedBody();

        return $this->parsedBody;
    }

    /**
     * @return array<mixed>
     */
    public function getBodyAsArray(): array
    {
        return $this->getParsedBody(jsonAsObject: true); // @phpstan-ignore-line (Method ...getBodyAsArray() should return array but returns array|object|null.)
    }

    public function getBodyAsObject(): object
    {
        return $this->getParsedBody(jsonAsObject: true); // @phpstan-ignore-line (Method ...getBodyAsArray() should return array but returns array|object|null.)
    }

    // --------------------------------------------------
    // query string methods

    /**
     * @return array<string, string>
     */
    public function getQueryParams(): array
    {
        return $this->psrRequest->getQueryParams();
    }

    public function getStringQueryOrThrow(string $name): string
    {
        $value = $this->psrRequest->getQueryParams()[$name] ?? null;
        if ($value === null) {
            throw new \UnexpectedValueException();
        }

        return $value;
    }

    public function getStringQueryOrNull(string $name): ?string
    {
        $params = $this->psrRequest->getQueryParams();
        if (isset($params[$name])) {
            return (string) $params[$name];
        }

        return null;
    }

    public function getStringQueryOrDefault(string $name, string $default): string
    {
        return (string) ($this->psrRequest->getQueryParams()[$name] ?? $default);
    }

    public function getIntQueryOrThrow(string $name): int
    {
        $value = $this->psrRequest->getQueryParams()[$name] ?? null;
        if ($value === null) {
            throw new \UnexpectedValueException();
        }

        return (int) $value;
    }

    public function getIntQueryOrNull(string $name): ?int
    {
        $params = $this->psrRequest->getQueryParams();
        if (isset($params[$name])) {
            return (int) $params[$name];
        }

        return null;
    }

    public function getIntQueryOrDefault(string $name, int $default): int
    {
        return (int) ($this->psrRequest->getQueryParams()[$name] ?? $default);
    }

    public function getFloatQueryOrThrow(string $name): float
    {
        $value = $this->psrRequest->getQueryParams()[$name] ?? null;
        if ($value === null) {
            throw new \UnexpectedValueException();
        }

        return (float) $value;
    }

    public function getFloatQueryOrNull(string $name): ?float
    {
        $params = $this->psrRequest->getQueryParams();
        if (isset($params[$name])) {
            return (float) $params[$name];
        }

        return null;
    }

    public function getFloatQueryOrDefault(string $name, float $default): float
    {
        return (float) ($this->psrRequest->getQueryParams()[$name] ?? $default);
    }

    public function getBoolQueryOrThrow(string $name): bool
    {
        $value = $this->psrRequest->getQueryParams()[$name] ?? null;
        if ($value === null) {
            throw new \UnexpectedValueException();
        }

        return (bool) $value;
    }

    public function getBoolQueryOrNull(string $name): ?bool
    {
        $params = $this->psrRequest->getQueryParams();
        if (isset($params[$name])) {
            return (bool) $params[$name];
        }

        return null;
    }

    public function getBoolQueryOrDefault(string $name, bool $default): bool
    {
        return (bool) ($this->psrRequest->getQueryParams()[$name] ?? $default);
    }

    /**
     * @return array<mixed>
     */
    public function getArrayQueryOrThrow(string $name): array
    {
        $value = $this->psrRequest->getQueryParams()[$name] ?? null;
        if ($value === null) {
            throw new \UnexpectedValueException();
        }

        return (array) $value;
    }

    /**
     * @return array<mixed>|null
     */
    public function getArrayQueryOrNull(string $name): ?array
    {
        $params = $this->psrRequest->getQueryParams();
        if (isset($params[$name])) {
            return (array) $params[$name];
        }

        return null;
    }

    /**
     * @param array<mixed> $default
     *
     * @return array<mixed>
     */
    public function getArrayQueryOrDefault(string $name, array $default): array
    {
        return (array) ($this->psrRequest->getQueryParams()[$name] ?? $default);
    }

    // --------------------------------------------------

    /**
     * @template T of object
     *
     * @param class-string<T> $fqcn
     *
     * @return T
     */
    public function hydrateBodyAsOne(string $fqcn): object
    {
        return $this->hydrator->hydrateOne($this->getBodyAsArray(), $fqcn);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $fqcn
     *
     * @return array<T>
     */
    public function hydrateBodyAsMany(string $fqcn): array
    {
        return $this->hydrator->hydrateMany($this->getBodyAsArray(), $fqcn); // @phpstan-ignore-line (Parameter #1 $rows of method ...::hydrateMany() expects array<array<string, mixed>>, array given.)
    }
}
