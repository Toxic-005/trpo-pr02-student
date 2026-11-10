<?php
declare(strict_types=1);
namespace Shop\Repository;
use Shop\Core\JsonFile;
use Shop\Model\{Product, Category};

final class ProductRepository
{
    private array $products = [];
    private array $categories = [];
    public function __construct(string $productsPath, ?string $categoriesPath = null)
    {
        $cartFormat = $categoriesPath === null;
        $categories = $cartFormat ? [['id' => 1, 'name' => 'Контрольные товары']] : JsonFile::list($categoriesPath);
        foreach ($categories as $row) {
            if (!is_array($row) || !is_int($row['id'] ?? null) || !is_string($row['name'] ?? null)) {
                throw new \RuntimeException('Некорректные данные категории.');
            }
            $category = new Category($row['id'], trim($row['name']));
            if (isset($this->categories[$category->id])) { throw new \RuntimeException('Повторяющийся id категории.'); }
            $this->categories[$category->id] = $category;
        }
        foreach (JsonFile::list($productsPath) as $row) {
            if (!is_array($row)) { throw new \RuntimeException('Ожидается объект товара.'); }
            // В dataset 9 нет категории и остатка: адаптер назначает одну категорию и учебный лимит 99.
            if ($cartFormat) { $row['category_id'] = 1; $row['stock'] = 99; }
            if (!is_int($row['id'] ?? null) || !is_string($row['name'] ?? null)
                || !(is_int($row['price'] ?? null) || is_float($row['price'] ?? null))
                || !is_int($row['category_id'] ?? null) || !is_int($row['stock'] ?? null)
                || !isset($this->categories[$row['category_id']])) {
                throw new \RuntimeException('Некорректные данные товара или неизвестная категория.');
            }
            $product = new Product($row['id'], trim($row['name']), (float) $row['price'], $this->categories[$row['category_id']], $row['stock']);
            if (isset($this->products[$product->id])) { throw new \RuntimeException('Повторяющийся id товара.'); }
            $this->products[$product->id] = $product;
        }
    }
    public function all(): array { return array_values($this->products); }
    public function categories(): array { return array_values($this->categories); }
    public function find(int $id): ?Product { return $this->products[$id] ?? null; }
    public function filter(?int $category, string $sort): array
    {
        if (!in_array($sort, ['name', 'price', 'price_desc'], true)) { throw new \InvalidArgumentException('Неизвестная сортировка.'); }
        $products = array_values(array_filter($this->products, static fn(Product $p): bool => $category === null || $p->category->id === $category));
        usort($products, static function (Product $a, Product $b) use ($sort): int {
            if ($sort === 'name') { return strnatcasecmp($a->name, $b->name) ?: $a->id <=> $b->id; }
            return ($sort === 'price' ? $a->priceCents <=> $b->priceCents : $b->priceCents <=> $a->priceCents) ?: $a->id <=> $b->id;
        });
        return $products;
    }
}
