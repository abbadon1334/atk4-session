<?php

declare(strict_types=1);

// if any file output something session will start and all test will fail
// this is a normal behaviour of PHP Session

require_once __DIR__.'/../vendor/autoload.php';

use Atk4\ATK4DBSession\SessionModel;
use Atk4\ATK4DBSession\Tests\SessionHandlerCallTracer;
use Atk4\Data\Persistence;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP;

$filter = new Filter();
$filter->includeDirectory('../src');

$coverage = new CodeCoverage(
    (new Selector())->forLineCoverage($filter),
    $filter
);

function coverage()
{
    global $coverage;
    $coverage->stop();

    $writer = new PHP();

    $writer->process($coverage, dirname(realpath(__FILE__)).'/../coverage/'.uniqid('sess', false).'.cov');
}

$coverage->start($_SERVER['SCRIPT_NAME']);

ob_start();

$persistence_filename = __DIR__.DIRECTORY_SEPARATOR.'dbsess.sqlite';

/** @var Atk4\Data\Persistence\Sql $p */
$p = Persistence::connect('sqlite:'.$persistence_filename);
$p->connection->connection()->executeQuery('
    CREATE TABLE IF NOT EXISTS session (
        id integer primary key autoincrement,
        session_id varchar(255),
        data text,
        created_on datetime,
        updated_on datetime
    );
');

$session_options = [
    'use_strict_mode' => 1,
    'use_trans_sid'   => 1,
];

if (isset($_GET['test_gc'])) {
    $session_options = [
        'gc_maxlifetime' => 60,
        'gc_divisor'     => 10,
        'gc_probability' => 1,
    ];
}

$session = new SessionHandlerCallTracer(new SessionModel($p), $session_options);

$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r): void {
    $r->addRoute('GET', '/ini_get/{key}', function ($key): void {
        echo $key.':'.((int) ini_get($key)).PHP_EOL;
    });

    $r->addRoute('GET', '/ping', function (): void {
        echo 'pong';
    });

    $r->addRoute('GET', '/session/sid', function (): void {
        echo '[SID]'.session_id().PHP_EOL;
    });

    $r->addRoute('GET', '/session/set/{key}/{value}', function ($key, $value): void {
        $_SESSION[$key] = $value;
    });

    $r->addRoute('GET', '/session/get/{key}', function ($key): void {
        $val = $_SESSION[$key] ?? null;
        echo '[VAL]'.$val.PHP_EOL;
    });

    $r->addRoute('GET', '/session/clear/{key}', function ($key): void {
        $_SESSION[$key] = '';
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

        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2] ?? [];
        call_user_func_array($handler, $vars);

        break;
}

coverage();
