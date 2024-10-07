<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

final class ArraySessionHandler implements \SessionHandlerInterface
{
    /**
     * @var array<string, string> The value is the serialized session data
     */
    private array $sessionsById = [];

    public function close(): bool
    {
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return false;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        return $this->sessionsById[$id] ?? false;
    }

    public function write(string $id, string $data): bool
    {
        $this->sessionsById[$id] = $data;

        return true;
    }

    public function destroy(string $id): bool
    {
        unset($this->sessionsById[$id]);

        return true;
    }
}
