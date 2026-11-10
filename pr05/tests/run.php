<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/Service/CatalogService.php';
set_error_handler(static function (int $severity, string $message): never { throw new ErrorException($message, 0, $severity); });
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
    echo 'PASS: ' . $label . PHP_EOL;
}
$catalog = new CatalogService(__DIR__ . '/../data');
check(count($catalog->products()) === 12, 'Все 12 товаров загружены');
check(count($catalog->categories()) === 3, 'Все категории загружены');
foreach ($catalog->products() as $product) {
    check($catalog->category($product->categoryId()) !== null, 'Связь категории товара ' . $product->id());
}
check(count($catalog->filterByCategory(1)) === 4, 'Фильтр по категории');
check($catalog->filterByCategory(999) === [], 'Неизвестная категория пуста');
check($catalog->averagePrice([]) === 0.0, 'Среднее пустой коллекции');
check($catalog->averagePrice($catalog->products()) === 1192.75, 'Средняя цена всех товаров');
$product = new Product(1, ' Пример ', 100.0, 1, false);
check($product->name() === 'Пример', 'Конструктор нормализует название');
check(!$product->isAvailable() && $product->belongsToCategory(1), 'Предметные методы статуса и категории');
check($product->discountedPrice(10) === 90.0 && $product->priceForQuantity(3) === 300.0, 'Скидка и стоимость количества');
check((new Product(2, 'Бесплатный', 0.0, 1))->isAvailable(), 'Нулевая цена и необязательный active');
foreach ([static fn() => new Product(0, 'Товар', 1, 1), static fn() => new Product(1, '', 1, 1),
    static fn() => new Product(1, 'Товар', -1, 1), static fn() => new Product(1, 'Товар', INF, 1),
    static fn() => new Product(1, 'Товар', 1, 0), static fn() => new Category(0, 'Категория'),
    static fn() => $product->discountedPrice(101), static fn() => $product->priceForQuantity(0)] as $index => $invalid) {
    try { $invalid(); throw new RuntimeException('Не было исключения'); }
    catch (InvalidArgumentException) { check(true, 'Некорректный конструктор/операция ' . $index); }
}
// CSV требований применяется как проверяемый контракт модели.
$handle = fopen(__DIR__ . '/../data/class_requirements.csv', 'rb');
fgetcsv($handle, 0, ';', '"', '');
while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
    if ($row === [null]) { continue; }
    [$class, $property, $type] = $row;
    $reflection = new ReflectionProperty($class, $property);
    check((string) $reflection->getType() === $type && $reflection->isPrivate(), $class . '::$' . $property . ' соответствует CSV');
}
fclose($handle);
echo "Checks: $checks\n";
