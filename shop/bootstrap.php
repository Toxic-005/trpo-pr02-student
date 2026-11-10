<?php
declare(strict_types=1);
spl_autoload_register(static function (string $class): void {
    $prefix = 'Shop\\';
    if (!str_starts_with($class, $prefix)) { return; }
    $path = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) { require $path; }
});
require_once __DIR__ . '/src/helpers.php';

use Shop\Core\{Application, JsonFile, Router, View};
use Shop\Controller\{HomeController, CatalogController, ErrorController};
use Shop\Repository\ProductRepository;

$view = new View(__DIR__ . '/views');
$errors = new ErrorController($view);
$home = new HomeController($view, JsonFile::object(__DIR__ . '/data/pr07/pages.json'));
$products = new ProductRepository(__DIR__ . '/data/pr08/products.json', __DIR__ . '/data/pr08/categories.json');
$catalog = new CatalogController($view, $products);
$router = new Router([$errors, 'notFound']);
$router->add('GET', '/', [$home, 'index']);
$router->add('GET', '/about', [$home, 'about']);
$router->add('GET', '/catalog', [$catalog, 'index']);
$router->add('GET', '/product/{id}', [$catalog, 'show']);
$router->add('GET', '/missing', [$errors, 'notFound']);
return new Application($router, $errors);
