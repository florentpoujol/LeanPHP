<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Http\AbstractResponse;
use LeanPHP\Http\HttpMiddlewareInterface;
use LeanPHP\Http\MiddlewareHandler;
use LeanPHP\Http\ServerRequest;

final readonly class SessionMiddleware implements HttpMiddlewareInterface
{
    public function handle(ServerRequest $request, MiddlewareHandler $handler): AbstractResponse
    {
        if (session_status() === \PHP_SESSION_NONE) {
            session_start();
        }

        $request->setSession(new Session());

        return $handler->handle($request);
    }
}