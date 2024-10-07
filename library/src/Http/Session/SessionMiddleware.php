<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Container;
use LeanPHP\Http\AbstractResponse;
use LeanPHP\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class SessionMiddleware implements MiddlewareInterface
{
    private const SESSION_COOKIE_NAME = 'leanphp_session';

    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
    ) {
    }

    public function process(ServerRequestInterface $psrRequest, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Container::getInstance()->get(ServerRequest::class);

        $session = new Session();

        $sessionIsBuiltIn = $this->sessionRepository instanceof PhpBuiltInSessionRepository;
        if ($sessionIsBuiltIn) {
            session_start();
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

        /** @var AbstractResponse $response */
        $response = $handler->handle($request->psrRequest);

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