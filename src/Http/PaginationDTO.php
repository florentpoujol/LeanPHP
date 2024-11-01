<?php

declare(strict_types=1);

namespace App\Http;

use LeanPHP\Validation\Validates;

final class PaginationDTO
{
    #[Validates(['>' => 5])]
    public int $page = 1;
    public int $perPage = 50;
    public string $sortDirection = 'asc';
}