<?php

declare(strict_types=1);

namespace Atk4\ATK4DBSession\Demos;

use Atk4\ATK4DBSession\SessionModel;
use Atk4\Data\Persistence;
use Atk4\Data\Schema\Migrator;

require_once __DIR__ . '/../init-autoloader.php';

$sqliteFile = __DIR__ . '/db.sqlite';
if (!file_exists($sqliteFile)) {
    new Persistence\Sql('sqlite:' . $sqliteFile);
}
unset($sqliteFile);

/** @var Persistence\Sql $db */
require_once __DIR__ . '/../init-db.php';

echo 'GITHUB_JOB : ' . getenv('GITHUB_JOB') . "\n\n";

if (getenv('GITHUB_JOB') === 'unit-test') {
    echo "skip db creation in create-db\n\n";

    return;
}

(new Migrator(new SessionModel($db)))->create();

echo "import complete!\n\n";
