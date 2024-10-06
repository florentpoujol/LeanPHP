<?php declare(strict_types=1);

namespace LeanPHP\Hasher;

interface HasherInterface
{
    public function hash(string $password): string;

    public function verify(string $password, string $hash): bool;

    public function needsRehash(string $hash): bool;
}