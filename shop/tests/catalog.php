<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
use Shop\Repository\ProductRepository;
use Shop\Model\{Product, Category};
$checks = 0;
$check = static function(bool $ok, string $label) use (&$checks): void {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    echo 'PASS: ' . $label . PHP_EOL; $checks++;
};
$repo = new ProductRepository(dirname(__DIR__) . '/data/pr08/products.json', dirname(__DIR__) . '/data/pr08/categories.json');
$check(count($repo->all()) === 24, 'Все 24 товара');
$check(count($repo->categories()) === 4, 'Четыре категории');
$file = fopen(dirname(__DIR__) . '/data/pr08/catalog_cases.csv', 'rb');
fgetcsv($file, 0, ';', '"', '');
while (($row = fgetcsv($file, 0, ';', '"', '')) !== false) {
    [$case, $category, $sort, $min] = $row;
    $items = $repo->filter($category === '' ? null : (int)$category, $sort);
    $check($case === 'unknown' ? $items === [] : count($items) >= (int)$min, $case . ': выборка dataset');
    if ($sort === 'price') { $prices = array_map(static fn(Product $p): int => $p->priceCents, $items); $sorted = $prices; sort($sorted); $check($prices === $sorted, 'Порядок цены'); }
}
fclose($file);
$check($repo->find(99999) === null, 'Неизвестный id');
$check($repo->find(11)->stock === 0, 'Нулевой остаток сохранён');
$check($repo->find(1)->priceCents === 57000, 'Цена в копейках');
try { new Product(1, 'Товар', -1, new Category(1, 'Книги'), 1); throw new LogicException('Нет исключения'); }
catch (InvalidArgumentException) { $check(true, 'Отрицательная цена отклонена'); }
echo 'Checks: ' . $checks . PHP_EOL;
