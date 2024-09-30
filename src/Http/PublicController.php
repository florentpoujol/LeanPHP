<?php declare(strict_types=1);

namespace App\Http;

use LeanPHP\Http\ServerRequest;
use LeanPHP\PhpViewRenderer;
use Nyholm\Psr7\Response;

final readonly class PublicController
{
    public function __construct(
        private PhpViewRenderer $viewRenderer,
        private ServerRequest $request,
    ) {
    }

    public function index(): Response
    {
        $html = $this->viewRenderer->render('home', [
            'varFromController' => 'la valeur de la var qui vient du controller',
            'queryString' => $this->request->getIntQueryOrDefault('the-query-string', 0),
            'queryArray' => $this->request->getArrayQueryOrNull('query'),
        ]);

        return new Response(body: $html);
    }
}