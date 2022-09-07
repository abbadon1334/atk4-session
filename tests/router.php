<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Tests;

// if any file output something session will start and all test will fail
// this is a normal behaviour of PHP Session

/** @var Persistence $db */
require_once __DIR__ . '/../demos/init-unit-tests.php';

use Atk4\ATK4DBSession\SessionModel;
use Atk4\Data\Persistence;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

ob_start(); // output blocked

$session_options = [
    'use_strict_mode' => 1,
    'gc_maxlifetime' => 60,
    'gc_divisor' => 10,
    'gc_probability' => isset($_GET['session-options-gc']) ? 1 : 0,
];

new SessionHandlerCallTracer(new SessionModel($db), $session_options);

$dispatcher = simpleDispatcher(function (RouteCollector $r): void {
    $r->addRoute('GET', '/ini_get/{key}', function ($key): void {
        echo $key . ':' . ((int) ini_get($key)) . PHP_EOL;
    });

    $r->addRoute('GET', '/ping', function (): void {
        echo 'pong';
    });

    $r->addRoute('GET', '/session/sid', function (): void {
        echo '[SID]' . session_id() . PHP_EOL;
    });

    $r->addRoute('GET', '/session/reset', function (): void {
        $_SESSION = [];
    });

    $r->addRoute('GET', '/session/set/{key}/{value}', function ($key, $value): void {
        $_SESSION[$key] = $value;
    });

    $r->addRoute('GET', '/session/get/{key}', function ($key): void {
        $val = $_SESSION[$key] ?? null;
        echo '[VAL]' . $val . PHP_EOL;
    });

    $r->addRoute('GET', '/session/unset/{key}', function ($key): void {
        unset($_SESSION[$key]);
    });

    $r->addRoute('GET', '/session/regenerate', function (): void {
        session_regenerate_id();
    });

    $r->addRoute('GET', '/session/regenerate/delete_old', function (): void {
        session_regenerate_id(true);
    });

    $r->addRoute('GET', '/session/destroy', function (): void {
        session_destroy();
    });

    $r->addRoute('GET', '/session/print', function (): void {
        print_r($_SESSION);
    });

    $r->addRoute('GET', '/session/gc-trigger', function (): void {
        // check for trigger of gc
    });
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        header('HTTP/1.0 404 Not Found');
        echo 'Error';

        break;
    case \FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2] ?? [];
        call_user_func_array($handler, $vars);

        break;
}
