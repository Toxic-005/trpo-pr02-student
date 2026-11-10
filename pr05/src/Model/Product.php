<?php
declare(strict_types=1);

final class Product
{
    public function __construct(
        private int $id,
        private string $name,
        private float $price,
        private int $categoryId,
        private bool $active = true,
    ) {
        $this->name = trim($name);
        if ($id <= 0 || $this->name === '' || !is_finite($price) || $price < 0 || $categoryId <= 0) {
            throw new InvalidArgumentException('Товар: неверный id, название, цена или категория.');
        }
    }

    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function price(): float { return $this->price; }
    public function categoryId(): int { return $this->categoryId; }
    public function isAvailable(): bool { return $this->active; }
    public function belongsToCategory(int $categoryId): bool { return $this->categoryId === $categoryId; }

    public function priceForQuantity(int $quantity): float
    {
        if ($quantity < 1 || $quantity > 1000) {
            throw new InvalidArgumentException('Количество должно быть от 1 до 1000.');
        }
        return round($this->price * $quantity, 2);
    }

    public function discountedPrice(float $percent): float
    {
        if (!is_finite($percent) || $percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Скидка должна быть от 0 до 100 процентов.');
        }
        return round($this->price * (1 - $percent / 100), 2);
    }
}
