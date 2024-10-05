<?php declare(strict_types=1);

namespace App\Http;

use App\Entities\LoginFormData;
use App\Entities\User;
use LeanPHP\Database\QueryBuilder;
use LeanPHP\Http\ServerRequest;
use LeanPHP\PhpViewRenderer;
use Nyholm\Psr7\Response;

final readonly class AuthController
{
    public function __construct(
        private PhpViewRenderer $viewRenderer,
        private ServerRequest $request,
    ) {
    }

    /**
     * Route: GET /auth/login
     */
    public function showLoginForm(): Response
    {
        $html = $this->viewRenderer->render('login', [
            //
        ]);

        return new Response(body: $html);
    }

    /**
     * Route: POST /auth/login
     */
    public function login(): Response
    {
        $loginForm = $this->request->hydrateBodyAsOne(LoginFormData::class);

        $passwordHash = password_hash($loginForm->password, \PASSWORD_BCRYPT);
        $user = User::getQueryBuilder()
            ->where('email', '=', $loginForm->email)
            ->where('password', '=', $passwordHash)
            ->selectSingle();

        // @phpstan-ignore-next-line
        if ($user === null || !password_verify($loginForm->password, $user->password)) {
            return new Response(302);
        }

        return new Response(302);
    }
}