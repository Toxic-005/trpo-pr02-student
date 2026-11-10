<?php
declare(strict_types=1);
spl_autoload_register(static function (string $class): void {
    $prefix = 'Shop\\';
    if (!str_starts_with($class, $prefix)) { return; }
    $path = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) { require $path; }
});
require_once __DIR__ . '/src/helpers.php';

use Shop\Core\{Application, JsonFile, Router, View, SessionStore};
use Shop\Controller\{HomeController, CatalogController, ErrorController, CartController};
use Shop\Repository\ProductRepository;
use Shop\Service\CartService;

$view = new View(__DIR__ . '/views');
$errors = new ErrorController($view);
$home = new HomeController($view, JsonFile::object(__DIR__ . '/data/pr07/pages.json'));
$cartDataset = getenv('SHOP_CATALOG') === 'cart';
$products = $cartDataset ? new ProductRepository(__DIR__ . '/data/pr09/products.json')
    : new ProductRepository(__DIR__ . '/data/pr08/products.json', __DIR__ . '/data/pr08/categories.json');
$catalog = new CatalogController($view, $products);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($cartDataset ? 'shop_cart_fixture' : 'shop_catalog');
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
}
$session = new SessionStore($_SESSION);
$cart = new CartService($products);
$cartController = new CartController($view, $cart, $session);
$view->setContext(static function () use ($session): array {
    $state = $session->get('cart', []);
    $count = is_array($state) ? array_sum(array_filter($state, 'is_int')) : 0;
    return ['cartCount' => $count, 'csrf' => $session->token(), 'flash' => $session->takeFlash()];
});
$router = new Router([$errors, 'notFound']);
$router->add('GET', '/', [$home, 'index']);
$router->add('GET', '/about', [$home, 'about']);
$router->add('GET', '/catalog', [$catalog, 'index']);
$router->add('GET', '/product/{id}', [$catalog, 'show']);
$router->add('GET', '/missing', [$errors, 'notFound']);
$router->add('GET', '/cart', [$cartController, 'index']);
$router->add('POST', '/cart/add', [$cartController, 'add']);
$router->add('POST', '/cart/set', [$cartController, 'set']);
$router->add('POST', '/cart/remove', [$cartController, 'remove']);
return new Application($router, $errors);
