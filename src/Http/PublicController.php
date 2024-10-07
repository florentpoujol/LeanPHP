<?php declare(strict_types=1);

namespace App\Http;

use App\Entities\User;
use LeanPHP\Http\Response;
use LeanPHP\Http\ServerRequest;
use LeanPHP\PhpViewRenderer;

final readonly class PublicController
{
    public function __construct(
        private PhpViewRenderer $viewRenderer,
        private ServerRequest $request,
    ) {
    }

    /**
     * Route: GET /
     */
    public function index(): Response
    {
        $session = $this->request->getSessionOrNull();
        $userId = $session?->getData('user_id');

        $user = null;
        if (\is_int($userId)) {
            $user = User::getQueryBuilder()
                ->where('id', '=', $userId)
                ->selectSingle();
        }

        $html = $this->viewRenderer->render('home', [
            'varFromController' => 'la valeur de la var qui vient du controller',
            'queryString' => $this->request->getIntQueryOrDefault('the-query-string', 0),
            'queryArray' => $this->request->getArrayQueryOrNull('query'),
            'user' => $user,
        ]);

        return new Response(body: $html);
    }
}