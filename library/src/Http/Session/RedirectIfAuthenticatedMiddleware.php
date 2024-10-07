<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Http\AbstractResponse;
use LeanPHP\Http\HttpMiddlewareInterface;
use LeanPHP\Http\MiddlewareHandler;
use LeanPHP\Http\RedirectResponse;
use LeanPHP\Http\ServerRequest;

final readonly class RedirectIfAuthenticatedMiddleware implements HttpMiddlewareInterface
{
    public function handle(ServerRequest $request, MiddlewareHandler $handler): AbstractResponse
    {
        $session = $request->getSessionOrNull();
        if ($session === null) {
            return $handler->handle($request);
        }

        if ($session->getData('user_id') === null) {
            return $handler->handle($request);
        }

        return new RedirectResponse('/blog');
    }
}