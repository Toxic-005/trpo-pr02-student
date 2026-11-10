<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/Service/CatalogService.php';
ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
error_reporting(E_ALL);
function e(string|int|float $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(float $value): string { return number_format($value, 2, ',', ' ') . ' ₽'; }
$error = null;
$filterError = null;
$products = [];
$categories = [];
$average = 0.0;
$categoryId = null;
try {
    $catalog = new CatalogService(__DIR__ . '/../data');
    $categories = $catalog->categories();
    $rawCategory = $_GET['category'] ?? '';
    if ($rawCategory !== '') {
        $parsed = is_string($rawCategory) ? filter_var($rawCategory, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($parsed === false) {
            $filterError = 'Категория должна быть положительным целым числом.';
            http_response_code(400);
        } else {
            $categoryId = $parsed;
        }
    }
    $products = $filterError === null ? $catalog->filterByCategory($categoryId) : [];
    $average = $catalog->averagePrice($products);
} catch (RuntimeException|InvalidArgumentException $exception) {
    http_response_code(503);
    $error = $exception->getMessage();
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Учебная лавка · ООП</title><link rel="stylesheet" href="styles.css"></head>
<body><header><span class="eyebrow">ТРПО / Практическая работа 05</span><h1>Учебная лавка</h1><p>Каталог на основе объектов Product и Category</p></header><main>
<?php if ($error !== null): ?><p class="error" role="alert"><?= e($error) ?></p><?php else: ?>
<section class="panel"><h2>Каталог товаров</h2><form method="get" action="index.php"><label for="category">Категория</label><select id="category" name="category"><option value="">Все категории</option>
<?php foreach ($categories as $category): ?><option value="<?= $category->id() ?>" <?= $categoryId === $category->id() ? 'selected' : '' ?>><?= e($category->name()) ?></option><?php endforeach ?></select><button>Применить</button> <a href="index.php">Сбросить</a></form>
<?php if ($filterError !== null): ?><p class="error" role="alert"><?= e($filterError) ?></p><?php endif ?>
<p class="muted">Товаров: <?= count($products) ?> · Средняя цена: <?= e(money($average)) ?></p></section>
<?php if (!$products): ?><p class="empty">В выбранной категории нет товаров.</p><?php endif ?>
<section class="cards" aria-label="Товары"><?php foreach ($products as $product): ?>
<article class="panel product" data-id="<?= $product->id() ?>"><span class="badge"><?= e($catalog->category($product->categoryId())->name()) ?></span><h2><?= e($product->name()) ?></h2>
<p class="price"><?= e(money($product->price())) ?></p><p class="<?= $product->isAvailable() ? 'available' : 'muted' ?>"><?= $product->isAvailable() ? 'Доступен' : 'Неактивен' ?></p>
<dl><dt>При скидке 10%</dt><dd><?= e(money($product->discountedPrice(10))) ?></dd><dt>Стоимость 3 единиц</dt><dd><?= e(money($product->priceForQuantity(3))) ?></dd></dl></article>
<?php endforeach ?></section><?php endif ?></main><footer>Практическая работа 5 · PHP и объектная модель</footer></body></html>
