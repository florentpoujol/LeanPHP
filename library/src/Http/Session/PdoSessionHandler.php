<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Database\QueryBuilder;
use LeanPHP\Identifiers\TimeBased16;

/**
 * The 'session.save_path' INI directive is the table name, by default "leanphp_http_sessions"
 */
final readonly class PdoSessionHandler implements
    \SessionHandlerInterface,
    \SessionIdInterface,
    \SessionUpdateTimestampHandlerInterface
{
    public function __construct(
        /**
         * @var QueryBuilder<Session> $queryBuilder
         */
        private readonly QueryBuilder $queryBuilder,
    ) {
    }

    public function close(): bool
    {
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return $this->queryBuilder
            ->reset()
            ->where('updated_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }

    public function open(string $path, string $name): bool
    {
        if ($path === '') {
            $path = 'leanphp_http_sessions';
        }

        $this->queryBuilder->inTable($path);

        return true;
    }

    public function read(string $id): string|false
    {
        $session = $this->queryBuilder
            ->reset()
            ->where('id', '=', $id)
            ->selectSingle(['data']);

        return $session['data'] ?? ''; // @phpstan-ignore-line (Cannot access offset 'data' on array<string, bool|int|string>|object.)
    }

    public function write(string $id, string $data): bool
    {
        return $this->queryBuilder
            ->reset()
            ->upsertSingle([
                'id' => $id,
                'data' => $data,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id']);
    }

    public function destroy(string $id): bool
    {
        return $this->queryBuilder
            ->reset()
            ->upsertSingle([
                'id' => $id,
                'data' => '',
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => date('Y-m-d H:i:s'),
            ], ['id']);
    }

    public function create_sid(): string
    {
        return (new TimeBased16())->getHex();
    }

    public function validateId(string $id): bool
    {
        return preg_match('/[a-z0-9]{32}/', $id) === 1;
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->queryBuilder
            ->reset()
            ->upsertSingle([
                'id' => $id,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id']);
    }
}
