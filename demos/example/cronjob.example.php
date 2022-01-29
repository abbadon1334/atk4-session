<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Demos;

include __DIR__ . '/../vendor/autoload.php';

use Atk4\ATK4DBSession\SessionModel;
use Atk4\Data\Persistence;

$p = Persistence::connect('mysql:dbname=atk4;host=localhost', 'atk4', '');

// Usually is not needed to run a forced GC, but if you need it, call it directly
(new \Atk4\ATK4DBSession\SessionHandler(new SessionModel($p)))->gc(3600);
