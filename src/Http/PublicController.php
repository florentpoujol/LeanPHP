<?php declare(strict_types=1);

namespace App\Http;

use App\Entities\User;
use LeanPHP\Http\Attributes\MapQueryString;
use LeanPHP\Http\Attributes\MapRouteParameter;
use LeanPHP\Http\Response;
use LeanPHP\Http\Route;
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

    /**
     * Route: GET /test/{slug}/{limit}?page=2
     */
    public function testRouteAction(
        ServerRequest $request,
        Route $route,
        // ?User $user = null,
        string $slug,
        #[MapRouteParameter('slug')] string $otherSlug,
        #[MapQueryString] int $page,
        #[MapQueryString('page')] int $otherPage,
        #[MapQueryString('whatever')] ?int $nonExistantQueryString2,
        #[MapQueryString(validate: false)] PaginationDTO $paginationDTO,
        #[MapQueryString('whatever')] int $nonExistantQueryString = 6,
        int $limit = 1,
        bool $test = false,
    ): Response
    {
        dd(
            $request::class, $route, /*$user,*/
            '----------',
            $slug, $limit, $test, $otherSlug,
            '----------',
            $request->getQueryParams(),   $page, $otherPage, $nonExistantQueryString, $nonExistantQueryString2,
            $paginationDTO,
        );
    }
}