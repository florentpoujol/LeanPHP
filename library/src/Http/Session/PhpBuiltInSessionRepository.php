<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

/**
 * @see https://www.php.net/manual/en/book.session.php
 */
final class PhpBuiltInSessionRepository implements SessionRepositoryInterface
{
    public function get(string $id): Session
    {
        return new Session(
            $id,
            $_SESSION,
        );
    }

    public function save(Session $session, ?string $oldId = null): void
    {
        if ($session->isDestroyed()) {
            $this->destroy($session);

            return;
        }

        if (session_id() !== $session->getId()) {
            session_regenerate_id(true);
        }

        $_SESSION = $session->getAllData();
    }

    public function destroy(?Session $session): void
    {
        $session?->destroy();
        session_regenerate_id(true);
        session_destroy();
    }
}