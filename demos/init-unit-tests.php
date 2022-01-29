<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Demos;

use Atk4\Core\Exception;
use Atk4\Data\Persistence;

date_default_timezone_set('UTC');

require_once __DIR__ . '/init-autoloader.php';

// collect coverage for HTTP tests 1/2
if (file_exists(__DIR__ . '/CoverageUtil.php') && !class_exists(\PHPUnit\Framework\TestCase::class, false)) {
    require_once __DIR__ . '/CoverageUtil.php';
    \CoverageUtil::start();
}

// collect coverage for HTTP tests 2/2
if (file_exists(__DIR__ . '/CoverageUtil.php') && !class_exists(\PHPUnit\Framework\TestCase::class, false)) {
    register_shutdown_function(function () {
        \CoverageUtil::saveData();
    });
}

try {
    /** @var Persistence\Sql $db */
    require_once __DIR__ . '/init-db.php';
} catch (\Throwable $e) {
    throw new Exception('Database error: ' . $e->getMessage());
}
