<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Database\QueryBuilder;

final class PdoSessionRepository implements SessionRepositoryInterface
{
    public function __construct(
        /**
         * @var QueryBuilder<Session> $queryBuilder
         */
        private readonly QueryBuilder $queryBuilder,
    ) {
        $queryBuilder
            ->inTable('leanphp_http_sessions')
            ->hydrate(Session::class);
    }

    public function get(string $id): Session
    {
        $session = $this->queryBuilder
            ->reset()
            ->where('id', '=', $id)
            ->selectSingle() ?? new Session();

        \assert($session instanceof Session);

        return $session;
    }

    public function save(Session $session, ?string $oldId = null): void
    {
        // if ($session->isDestroyed()) {
        //     $this->destroy($session);
        //
        //     return;
        // }

        $this->queryBuilder
            ->reset()
            ->upsertSingle([
                'id' => $session->getId(),
                // 'data' => json_encode($session->getAllData()),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id']);

        if ($oldId !== null && $oldId !== $session->getId()) {
            $this->queryBuilder
                ->reset()
                ->where('id', '=', $oldId)
                ->delete();
        }
        // Note Florent: terminating the old session like may not always be a good idea.
        // Particularly in AJAX context, it may be more pertinent to let the old session live a few more seconds.
    }

    public function destroy(?Session $session): void
    {
        if ($session === null) {
            return;
        }

        $this->queryBuilder
            ->reset()
            ->where('id', '=', $session->getId())
            ->delete();
        // Note Florent: this doesn't work because calling $session->destroy() changes the sessionId..
    }
}