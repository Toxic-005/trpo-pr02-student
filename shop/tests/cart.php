<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
use Shop\Core\{JsonFile, HttpException, SessionStore};
use Shop\Repository\ProductRepository;
use Shop\Service\CartService;
$checks = 0;
$check = static function(bool $ok, string $label) use (&$checks): void { if (!$ok) { throw new RuntimeException('FAIL: ' . $label); } echo 'PASS: ' . $label . PHP_EOL; $checks++; };
$repo = new ProductRepository(dirname(__DIR__) . '/data/pr09/products.json');
$cart = new CartService($repo);
foreach (JsonFile::list(dirname(__DIR__) . '/data/pr09/cart_scenarios.json') as $number => $scenario) {
    $state = [];
    foreach ($scenario['steps'] as $step) {
        $parts = explode(':', $step);
        $state = $cart->change($state, $parts[0], (int)$parts[1], (int)($parts[2] ?? 1));
    }
    $expected = $scenario['expected'];
    ksort($state); ksort($expected);
    $check($state === $expected, 'Состав dataset сценария ' . ($number+1));
    $summary = $cart->summary($state);
    $expectedTotal = 0;
    foreach ($expected as $id => $qty) { $expectedTotal += $repo->find((int)$id)->priceCents * $qty; }
    $check($summary['count'] === array_sum($expected) && $summary['total'] === $expectedTotal, 'Количество и сумма сценария ' . ($number+1));
}
foreach ([0,-1,100] as $qty) {
    try { $cart->change([], 'add', 1, $qty); throw new LogicException('Количество принято'); }
    catch (HttpException $exception) { $check($exception->status === 422, 'Неверное количество ' . $qty); }
}
try { $cart->change([], 'add', 99999); throw new LogicException('Товар принят'); }
catch (HttpException $exception) { $check($exception->status === 404, 'Неизвестный товар'); }
$state=[]; $session = new SessionStore($state);
$session->flash('Один раз');
$check($session->takeFlash() === 'Один раз' && $session->takeFlash() === null, 'Flash только один раз');
$session->verifyToken($session->token());
try { $session->verifyToken('bad'); throw new LogicException('Токен принят'); }
catch (HttpException $exception) { $check($exception->status === 403, 'CSRF отклонён'); }
echo 'Checks: ' . $checks . PHP_EOL;
