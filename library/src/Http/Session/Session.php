<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Identifiers\TimeBased16;

final class Session
{
    private string $id;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct()
    {
        $this->regenerateId();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function regenerateId(): void
    {
        $this->id = (new TimeBased16())->getHex();
    }

    /**
     * @return array<mixed>
     */
    public function getAllData(): array
    {
        return $this->data;
    }

    public function getData(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function setData(string $key, mixed $data): void
    {
        $this->data[$key] = $data;
    }

    /**
     * @param array<mixed> $data
     */
    public function mergeData(string $key, mixed $data): void
    {
        $previousData = $this->data[$key] ?? [];
        \assert(\is_array($previousData));

        $this->data[$key] = array_merge($previousData, $data);
    }
}