<?php declare(strict_types=1);

// unlike when using define(), it seems OK if the const is defined several times because the file is required several times

const BASE_APP_PATH = __DIR__ . '/..';

const TEST_ENV_NAME = 'test'; // if you change this, also change it in the PHPUnit confi
