<?php declare(strict_types=1);

namespace App;

use Nyholm\Psr7\Response;

final readonly class PublicController
{
    public function index(): Response
    {
        return new Response(200, body: "hello from public controller");
    }
}