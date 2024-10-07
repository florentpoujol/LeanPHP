<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

use App\Entities\User;
use LeanPHP\Http\AbstractResponse;
use LeanPHP\Http\HttpMiddlewareInterface;
use LeanPHP\Http\MiddlewareHandler;
use LeanPHP\Http\Response;
use LeanPHP\Http\ServerRequest;

final readonly class MustBeLoggedInMiddleware implements HttpMiddlewareInterface
{
    public function handle(ServerRequest $request, MiddlewareHandler $handler): AbstractResponse
    {
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

        return $handler->handle($request);
    }
}