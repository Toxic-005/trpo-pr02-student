<?php
declare(strict_types=1);
use Shop\Core\Request;
ini_set('display_errors', '0');
error_reporting(E_ALL);
$application = require dirname(__DIR__) . '/bootstrap.php';
$application->handle(Request::fromGlobals())->send();
