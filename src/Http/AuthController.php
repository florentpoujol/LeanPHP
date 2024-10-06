<?php declare(strict_types=1);

namespace App\Http;

use App\Entities\LoginFormData;
use App\Entities\User;
use LeanPHP\Hasher\HasherInterface;
use LeanPHP\Http\ServerRequest;
use LeanPHP\PhpViewRenderer;
use LeanPHP\Validation\ValidatorInterface;
use Nyholm\Psr7\Response;

final readonly class AuthController
{
    public function __construct(
        private PhpViewRenderer $viewRenderer,
        private ServerRequest $request,
        private HasherInterface $hasher,
        private ValidatorInterface $validator,
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

        if (! $this->validator->setData($loginForm)->isValid()) {
            // flash errors to the sessions
            return new Response(302, body: 'validation errors');
        }

        $user = User::getQueryBuilder()
            ->where('email', '=', $loginForm->email)
            ->selectSingle();

        // @phpstan-ignore-next-line
        if ($user === null || !$this->hasher->verify($loginForm->password, $user->password)) {
            // flash errors to the sessions
            return new Response(302, body: 'user not found or wrong password');
        }

        return new Response(302, body: 'login success');
    }
}