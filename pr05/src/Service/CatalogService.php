<?php
declare(strict_types=1);
require_once __DIR__ . '/../Model/Product.php';
require_once __DIR__ . '/../Model/Category.php';

final class CatalogService
{
    /** @var Product[] */
    private array $products = [];
    /** @var array<int, Category> */
    private array $categories = [];

    public function __construct(string $dataDirectory)
    {
        foreach ($this->readJson($dataDirectory . '/categories.json') as $row) {
            if (!is_array($row) || !is_int($row['id'] ?? null) || !is_string($row['name'] ?? null)) {
                throw new RuntimeException('Некорректная категория в dataset.');
            }
            $category = new Category($row['id'], $row['name']);
            if (isset($this->categories[$category->id()])) {
                throw new RuntimeException('Повторяющийся id категории.');
            }
            $this->categories[$category->id()] = $category;
        }
        $seen = [];
        foreach ($this->readJson($dataDirectory . '/products.json') as $row) {
            if (!is_array($row) || !is_int($row['id'] ?? null) || !is_string($row['name'] ?? null)
                || !(is_int($row['price'] ?? null) || is_float($row['price'] ?? null))
                || !is_int($row['category_id'] ?? null)
                || (isset($row['active']) && !is_bool($row['active']))) {
                throw new RuntimeException('Некорректные поля товара в dataset.');
            }
            $product = new Product($row['id'], $row['name'], (float) $row['price'], $row['category_id'], $row['active'] ?? true);
            if (!isset($this->categories[$product->categoryId()]) || isset($seen[$product->id()])) {
                throw new RuntimeException('Неизвестная категория или повторяющийся id товара.');
            }
            $seen[$product->id()] = true;
            $this->products[] = $product;
        }
    }

    private function readJson(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Не удалось прочитать данные каталога.');
        }
        try {
            $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Повреждён JSON каталога.', 0, $exception);
        }
        if (!str_starts_with(ltrim($raw), '[') || !is_array($rows) || !array_is_list($rows)) {
            throw new RuntimeException('В JSON каталога ожидается список.');
        }
        return $rows;
    }

    public function products(): array { return $this->products; }
    public function categories(): array { return array_values($this->categories); }
    public function category(int $id): ?Category { return $this->categories[$id] ?? null; }

    public function filterByCategory(?int $id): array
    {
        return $id === null ? $this->products : array_values(array_filter($this->products,
            static fn(Product $product): bool => $product->belongsToCategory($id)));
    }

    public function averagePrice(array $products): float
    {
        if (!$products) { return 0.0; }
        return round(array_sum(array_map(static fn(Product $product): float => $product->price(), $products)) / count($products), 2);
    }
}
