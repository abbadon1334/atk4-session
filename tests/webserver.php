<?php

declare(strict_types=1);

// if any file output something session will start and all test will fail
// this is a normal behaviour of PHP Session

require_once __DIR__.'/../vendor/autoload.php';

ob_start();

$persistence_filename = __DIR__.DIRECTORY_SEPARATOR.'dbsess.sqlite';

$p = new \atk4\data\Persistence\SQL('sqlite:'.$persistence_filename);
$p->connection->connection()->exec('
    CREATE TABLE IF NOT EXISTS session
    (
        id integer
            primary key autoincrement,
        session_id varchar(255),
        data text,
        created_on datetime,
        updated_on datetime
    );
');

$session = new \atk4\ATK4DBSession\tests\SessionHandlerCallTracer($p, 60 * 60, 100);

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r): void {
    $r->addRoute('GET', '/ping', function (): void {
        echo 'pong';
    });

    $r->addRoute('GET', '/session/sid', function (): void {
        $sid = session_id();
        echo '[SID]'.$sid.PHP_EOL;
    });

    $r->addRoute('GET', '/session/set/{key}/{value}', function ($key, $value): void {
        $_SESSION[$key] = $value;
    });

    $r->addRoute('GET', '/session/get/{key}', function ($key): void {
        $v = $_SESSION[$key] ?? null;
        echo '[VAL]'.$v.PHP_EOL;
    });

    $r->addRoute('GET', '/session/clear/{key}', function ($key): void {
        $_SESSION[$key] = '';
    });

    $r->addRoute('GET', '/session/regenerate', function (): void {
        session_regenerate_id(true);
    });

    $r->addRoute('GET', '/session/destroy', function (): void {
        session_destroy();
    });

    $r->addRoute('GET', '/session/print', function (): void {
        print_r($_SESSION);
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
    case FastRoute\Dispatcher::NOT_FOUND:
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        header('HTTP/1.0 404 Not Found');
        echo 'Error';
        exit;
    break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2] ?? [];
        call_user_func_array($handler, $vars);
    break;
}
