<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use LeanPHP\Container;
use LeanPHP\Http\RedirectResponse;
use LeanPHP\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function process(ServerRequestInterface $psrRequest, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Container::getInstance()->get(ServerRequest::class);

        $session = $request->getSessionOrNull();
        if ($session === null) {
            return $handler->handle($psrRequest);
        }

        if ($session->getData('user_id') === null) {
            return $handler->handle($psrRequest);
        }

        return new RedirectResponse('/blog');
    }
}