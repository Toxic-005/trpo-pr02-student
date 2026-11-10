<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
use Shop\Core\{JsonFile, HttpException};
use Shop\Repository\{OrderRepository, ProductRepository};
use Shop\Service\{OrderValidator, CartService, CheckoutService};
$checks = 0;
$check = static function(bool $ok, string $label) use (&$checks): void { if (!$ok) { throw new RuntimeException('FAIL: ' . $label); } echo 'PASS: ' . $label . PHP_EOL; $checks++; };
$validator = new OrderValidator(JsonFile::list(dirname(__DIR__) . '/data/pr10/delivery_methods.json'));
foreach (JsonFile::list(dirname(__DIR__) . '/data/pr10/order_cases.json') as $case) {
    $check(($validator->validate($validator->normalize($case['input'])) === []) === $case['valid'], 'order_cases: ' . $case['case']);
}
$valid = $validator->normalize(['name'=>'Иван','email'=>'ivan@example.test','delivery'=>'pickup']);
$check(isset($validator->validate(array_replace($valid,['delivery'=>'unknown']))['delivery']), 'Неизвестная доставка');
$check(isset($validator->validate(array_replace($valid,['delivery'=>'courier']))['address']), 'Курьер без адреса');
$check(isset($validator->validate(array_replace($valid,['name'=>str_repeat('Я',101)]))['name']), 'Длинное имя');
$directory = dirname(__DIR__) . '/storage/test-' . bin2hex(random_bytes(6));
$repo = new ProductRepository(dirname(__DIR__) . '/data/pr09/products.json');
$service = new CheckoutService(new CartService($repo),$validator,new OrderRepository($directory,dirname(__DIR__) . '/data/pr10/orders_seed.json'));
try {
    $receipt = $service->place($valid,[1=>2,2=>1]);
    $check($receipt['total_cents'] === 430000 && $receipt['delivery'] === 'Самовывоз', 'Сумма рассчитывается на сервере');
    $saved = JsonFile::list($directory.'/orders.json');
    $check(count($saved) === 1 && $saved[0]['contact']['email'] === 'ivan@example.test', 'JSON заказа сохранён');
    $courier = $service->place(array_replace($valid,['delivery'=>'courier','address'=>'Учебная улица, 1']),[1=>1]);
    $check($courier['total_cents'] === 135000 && $courier['id'] !== $receipt['id'], 'Курьер +450 рублей и уникальный id');
    try { $service->place($valid,[]); throw new LogicException('Пустая корзина принята'); }
    catch (HttpException $exception) { $check($exception->status === 409, 'Пустая корзина запрещена'); }
    file_put_contents($directory.'/orders.json','{broken');
    try { $service->place($valid,[1=>1]); throw new LogicException('Повреждение принято'); }
    catch (RuntimeException) { $check(file_get_contents($directory.'/orders.json') === '{broken', 'Повреждённый файл не перезаписан'); }
} finally {
    foreach (glob($directory.'/*') ?: [] as $file) { unlink($file); }
    if (is_dir($directory)) { rmdir($directory); }
}
echo 'Checks: ' . $checks . PHP_EOL;
