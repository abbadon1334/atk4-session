<?php

declare(strict_types=1);

include __DIR__.'/../vendor/autoload.php';

use Atk4\ATK4DBSession\SessionModel;
use Atk4\Data\Persistence;

$p = Persistence::connect('mysql:dbname=atk4;host=localhost', 'atk4', '');

// init SessionHandler
new \Atk4\ATK4DBSession\SessionHandler(new SessionModel($p));

print_r($_SESSION['test']);

$_SESSION['test'] = 1;
