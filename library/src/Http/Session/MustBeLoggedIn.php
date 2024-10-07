<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use App\Entities\User;
use LeanPHP\Container;
use LeanPHP\Http\ServerRequest;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class MustBeLoggedIn implements MiddlewareInterface
{
    public function process(ServerRequestInterface $psrRequest, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Container::getInstance()->get(ServerRequest::class);

        $session = $request->getSessionOrNull();
        $userId = $session?->getData('user_id');

        $user = null;
        if (\is_int($userId)) {
            $user = User::getQueryBuilder()
                ->where('id', '=', $userId)
                ->selectSingle();
        }

        if ($user === null) {
            return new Response(403, body: 'User not authenticated');
        }

        // save the user in the request object or the container ?

        return $handler->handle($psrRequest);
    }
}