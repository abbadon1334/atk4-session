<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Demos;

$isRootProject = file_exists(__DIR__ . '/../vendor/autoload.php');
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require dirname(__DIR__, $isRootProject ? 1 : 4) . '/vendor/autoload.php';
if (!$isRootProject && !class_exists(\Atk4\ATK4DBSession\Tests\BaseTestCase::class)) {
    throw new \Error('Demos can be run only if Atk4/ATK4DBSession is a root composer project or if dev files are autoloaded');
}
unset($isRootProject, $loader);
