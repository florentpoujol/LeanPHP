<?php declare(strict_types=1);

namespace LeanPHP\Http;

final class RedirectResponse extends AbstractResponse
{
    public function __construct(string $location, int $status = 302)
    {
        parent::__construct($status, ['Location' => $location]);
    }
}