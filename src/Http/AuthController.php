<?php declare(strict_types=1);

namespace App\Http;

use App\Entities\LoginFormData;
use App\Entities\User;
use LeanPHP\Hasher\HasherInterface;
use LeanPHP\Http\RedirectResponse;
use LeanPHP\Http\Response;
use LeanPHP\Http\ServerRequest;
use LeanPHP\PhpViewRenderer;
use LeanPHP\Validation\FormHtmlValidationBuilder;
use LeanPHP\Validation\ServerRequestValidator;

final readonly class AuthController
{
    public function __construct(
        private PhpViewRenderer $viewRenderer,
        private ServerRequest $request,
        private HasherInterface $hasher,
        private FormHtmlValidationBuilder $formValidationBuilder,
    ) {
    }

    /**
     * Route: GET /auth/login
     */
    public function showLoginForm(): Response
    {
        $this->formValidationBuilder->setEntityFqcn(LoginFormData::class);

        $html = $this->viewRenderer->render('login', [
            'formBuilder' => $this->formValidationBuilder,
        ]);

        return new Response(body: $html);
    }

    /**
     * Route: POST /auth/login
     *
     */
    public function login(ServerRequestValidator $validator): RedirectResponse
    {
        $validator->setEntityFqcn(LoginFormData::class);

        // $validator->validatorOrThrow();
        if (! $validator->validate()) {
            return new RedirectResponse('/auth/login');
        }

        /** @var LoginFormData $formEntity */
        $formEntity = $validator->getValidatedEntity();

        /** @var null|User $user */
        $user = User::getQueryBuilder()
            ->where('email', '=', $formEntity->email)
            ->selectSingle();

        $session = $this->request->getSessionOrThrow();

        if ($user === null || !$this->hasher->verify($formEntity->password, $user->password)) {
            $session->setData('validation_errors', ['wrong email or password']);

            return new RedirectResponse('/auth/login');
        }

        $session->regenerateId();
        $session->setData('user_id', $user->id);

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