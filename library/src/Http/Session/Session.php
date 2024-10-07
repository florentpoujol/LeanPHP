<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

final class Session
{
    public function getId(): string
    {
        $sessionId = session_id();
        \assert(\is_string($sessionId));

        return $sessionId;
    }

    public function regenerateId(): void
    {
        session_regenerate_id();
    }

    public function getData(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    public function setData(string $key, mixed $data): void
    {
        $_SESSION[$key] = $data;
    }

    public function destroy(): void
    {
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }

        session_regenerate_id();
    }
}
