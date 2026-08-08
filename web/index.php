<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/ErrorHandler.php';

use app\App;
use app\ErrorHandler;
use app\Response;
use app\ResponseEmitter;

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
ErrorHandler::register();

$app = new App();
$result = $app->run();

if ($result instanceof Response) {
    (new ResponseEmitter())->emit($result);
} else {
    echo $result;
}
