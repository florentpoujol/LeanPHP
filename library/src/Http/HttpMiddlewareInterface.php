<?php declare(strict_types=1);

namespace LeanPHP\Http;

interface HttpMiddlewareInterface
{
    /**
     * @param callable(ServerRequest): AbstractResponse $next
     */
    public function handle(ServerRequest $request, callable $next): AbstractResponse;
}