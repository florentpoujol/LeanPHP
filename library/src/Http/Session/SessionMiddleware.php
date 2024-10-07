<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Http\AbstractResponse;
use LeanPHP\Http\HttpMiddlewareInterface;
use LeanPHP\Http\ServerRequest;

final readonly class SessionMiddleware implements HttpMiddlewareInterface
{
    private const SESSION_COOKIE_NAME = 'leanphp_session';

    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @param callable(ServerRequest): AbstractResponse $next
     */
    public function handle(ServerRequest $request, callable $next): AbstractResponse
    {
        $session = new Session();

        $sessionIsBuiltIn = $this->sessionRepository instanceof PhpBuiltInSessionRepository;
        if ($sessionIsBuiltIn) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $sessionId = session_id();
            \assert(\is_string($sessionId));
            $session = $this->sessionRepository->get($sessionId);
        } else {
            $sessionId = $request->getCookieOrNull(self::SESSION_COOKIE_NAME);

            if ($sessionId !== null) {
                $session = $this->sessionRepository->get($sessionId);
            }
        }

        $request->setSession($session);

        $response = $next($request);

        $this->sessionRepository->save($session, $sessionId);

        if (! $sessionIsBuiltIn) {
            if ($session->isDestroyed()) {
                $response->deleteCookie(self::SESSION_COOKIE_NAME);
            } else {
                $response->setCookie(self::SESSION_COOKIE_NAME, $session->getId());
            }
        }

        return $response;
    }
}