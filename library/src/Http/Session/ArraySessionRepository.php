<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

final class ArraySessionRepository implements SessionRepositoryInterface
{
    /**
     * @var array<string, Session>
     */
    private array $sessionsById = [];

    public function get(string $id): Session
    {
        return $this->sessionsById[$id] ?? new Session();
    }

    public function save(Session $session, ?string $oldId = null): void
    {
        if ($session->isDestroyed()) {
            $this->destroy($session);

            return;
        }

        if ($oldId !== null) {
            unset($this->sessionsById[$oldId]);
        }

        $this->sessionsById[$session->getId()] = $session;
    }

    public function destroy(?Session $session): void
    {
        if ($session !== null) {
            unset($this->sessionsById[$session->getId()]);
        }
    }
}