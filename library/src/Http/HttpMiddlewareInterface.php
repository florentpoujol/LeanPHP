<?php declare(strict_types=1);

namespace LeanPHP\Http;

interface HttpMiddlewareInterface
{
    public function handle(ServerRequest $request, MiddlewareHandler $handler): AbstractResponse;
}