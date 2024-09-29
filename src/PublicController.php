<?php declare(strict_types=1);

namespace App;

use LeanPHP\PhpViewRenderer;
use Nyholm\Psr7\Response;

final readonly class PublicController
{
    public function __construct(
        private PhpViewRenderer $viewRenderer,
    ) {
    }

    public function index(): Response
    {
        $html = $this->viewRenderer->render('home', [
            'varFromController' => 'la valeur de la var qui vient du controller',
        ]);

        return new Response(body: $html);
    }
}