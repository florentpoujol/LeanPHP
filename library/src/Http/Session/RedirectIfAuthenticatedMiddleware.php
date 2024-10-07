<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Http\AbstractResponse;
use LeanPHP\Http\HttpMiddlewareInterface;
use LeanPHP\Http\RedirectResponse;
use LeanPHP\Http\ServerRequest;

final readonly class RedirectIfAuthenticatedMiddleware implements HttpMiddlewareInterface
{
    /**
     * @param callable(ServerRequest): AbstractResponse $next
     */
    public function handle(ServerRequest $request, callable $next): AbstractResponse
    {
        $session = $request->getSessionOrNull();
        if ($session === null) {
            return $next($request);
        }

        if ($session->getData('user_id') === null) {
            return $next($request);
        }

        return new RedirectResponse('/blog');
    }
}