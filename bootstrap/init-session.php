<?php declare(strict_types=1);

use LeanPHP\Container\Container;
use LeanPHP\Http\Session\PdoSessionHandler;

/**
 * @var Container $container
 *
 * @see https://www.php.net/manual/en/session.configuration.php
 */

/*
 *
 * The PHPRedis extension provide its own session handler, see their doc
 * https://github.com/phpredis/phpredis?tab=readme-ov-file#php-session-handler
 */
// ini_set('session.save_handler', 'files');

// OR

// set the $customSaveHandlerFqcn variable to a user-land class that implements SessionHandlerInterface
$customSaveHandlerFqcn = PdoSessionHandler::class;


// the value depends on the save handler
ini_set('session.save_path', '');

// --------------------------------------------------
// some other settings

ini_set('session.name', 'PHPSESSID'); // cookie name
ini_set('session.cookie_lifetime', '0'); // in seconds; 0 = browser session
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

// ini_set('session.auto_start', '1'); // when set to 1 all routes will start a session, and the StartSessionMiddleware become no useful
ini_set('session.use_strict_mode', '1'); // keep at '1'

// --------------------------------------------------
// register custom handlers, if any

session_set_save_handler($container->getInstance($customSaveHandlerFqcn));
