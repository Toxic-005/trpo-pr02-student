<?php
declare(strict_types=1);
use Shop\Core\Request;
ini_set('display_errors', '0');
error_reporting(E_ALL);
try {
    $application = require dirname(__DIR__) . '/bootstrap.php';
    $application->handle(Request::fromGlobals())->send();
} catch (Throwable $exception) {
    // Ошибки загрузки dataset обрабатываются даже до создания Application.
    error_log('Shop bootstrap: ' . get_class($exception));
    $view = new Shop\Core\View(dirname(__DIR__) . '/views');
    $errors = new Shop\Controller\ErrorController($view);
    $errors->show(500, getenv('APP_ENV') === 'development' ? $exception->getMessage()
        : 'Не удалось загрузить магазин. Попробуйте позже.')->send();
}
