<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/bootstrap',
        __DIR__ . '/library',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        earlyReturn: true,
        strictBooleans: true,
    )
    ->withRules([
        ReadOnlyPropertyRector::class,
    ])
    ;