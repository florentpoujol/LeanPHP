<?php declare(strict_types=1);

namespace App\Http;

use App\Entities\LoginFormData;
use App\Entities\User;
use LeanPHP\Hasher\HasherInterface;
use LeanPHP\Http\RedirectResponse;
use LeanPHP\Http\Response;
use LeanPHP\Http\ServerRequest;
use LeanPHP\PhpViewRenderer;
use LeanPHP\Validation\ValidatorInterface;

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
    public function login(): RedirectResponse
    {
        $session = $this->request->getSessionOrThrow();

        $loginForm = $this->request->hydrateBodyAsOne(LoginFormData::class);

        if (! $this->validator->setData($loginForm)->isValid()) {
            $session->setData('validation_errors', $this->validator->getMessages());

            return new RedirectResponse('/auth/login');
        }

        /** @var null|User $user */
        $user = User::getQueryBuilder()
            ->where('email', '=', $loginForm->email)
            ->selectSingle();

        if ($user === null || !$this->hasher->verify($loginForm->password, $user->password)) {
            $session->setData('validation_errors', ['wrong email or password']);

            return new RedirectResponse('/auth/login');
        }

        $session->setData('user_id', $user->id);
        $session->regenerateId();

        return new RedirectResponse('/');
    }

    /**
     * Route: GET /auth/logout
     */
    public function logout(): RedirectResponse
    {
        $this->request->getSessionOrNull()?->destroy();

        return new RedirectResponse('/');
    }
}