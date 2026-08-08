<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$container = new app\Container();
$app = new app\console\ConsoleApplication($container);
exit($app->run($argv));
