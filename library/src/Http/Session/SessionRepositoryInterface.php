<?php declare(strict_types=1);

namespace LeanPHP\Http\Session;

interface SessionRepositoryInterface
{
    public function get(string $id): Session;

    public function save(Session $session, null|string $oldId = null): void;

    public function destroy(?Session $session): void;
}