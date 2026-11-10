<?php
declare(strict_types=1);
namespace Shop\Model;
final class Product
{
    public readonly int $priceCents;
    public function __construct(public readonly int $id, public readonly string $name, float $price,
        public readonly Category $category, public readonly int $stock)
    {
        if ($id <= 0 || trim($name) === '' || !is_finite($price) || $price < 0 || $price > 10000000 || $stock < 0) {
            throw new \InvalidArgumentException('Некорректные свойства товара.');
        }
        $this->priceCents = (int) round($price * 100);
    }
}
