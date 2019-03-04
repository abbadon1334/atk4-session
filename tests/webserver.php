<?php

require_once __DIR__ . '/../vendor/autoload.php';


$persistence_filename = __DIR__ . DIRECTORY_SEPARATOR . 'dbsess.sqlite';

$p = new \atk4\data\Persistence_SQL('sqlite:' . $persistence_filename);

(new \atk4\schema\Migration\SQLite(new \atk4\ATK4DBSession\SessionModel($p)))->migrate();

$session = new \atk4\ATK4DBSession\tests\SessionHandlerCallTracer($p, 60 * 60, 100);

/**
When it will catch errors

$api = new \atk4\api\Api();

// Simple handling of GET request through a callback.
$api->get('/ping', function () {
    return 'Pong';
});

// Methods can accept arguments, and everything is type-safe.
$api->get('/hello/:name', function ($name) {
    return "Hello, $name";
});

*/
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    
    $r->addRoute('GET', '/ping', function() {
        echo 'pong';
    });
    
    $r->addRoute('GET', '/session/sid', function() {
        $sid = session_id();
        echo '[SID]' . $sid . PHP_EOL;
    });
    
    $r->addRoute('GET', '/session/set/{key}/{value}', function($key,$value) {
        $_SESSION[$key] = $value;
    });
    
    $r->addRoute('GET', '/session/get/{key}', function($key) {
        echo '[VAL]' . $_SESSION[$key] . PHP_EOL;
    });
    
    $r->addRoute('GET', '/session/clear/{key}', function($key) {
        $_SESSION[$key] = '';
    });
    
    $r->addRoute('GET', '/session/regenerate', function() {
        session_regenerate_id(true);
    });
    
    $r->addRoute('GET', '/session/destroy', function() {
        session_destroy();
    });
    
    $r->addRoute('GET', '/session/print', function() {
        print_r($_SESSION);
    });
    
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        header("HTTP/1.0 404 Not Found");
        echo "Error";
        exit;
    break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        call_user_func_array($handler,$vars);
        exit;
    break;
}
