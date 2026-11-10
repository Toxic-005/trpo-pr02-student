<?php
declare(strict_types=1);
namespace Shop\Model;
final class OrderData
{
    public function __construct(public readonly string $name, public readonly string $email,
        public readonly string $address, public readonly string $deliveryId, public readonly string $deliveryTitle,
        public readonly int $deliveryCents, public readonly array $items, public readonly int $goodsCents)
    {
        if (trim($name) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$items || $goodsCents < 0 || $deliveryCents < 0) {
            throw new \InvalidArgumentException('Невалидное состояние заказа.');
        }
    }
    public function total(): int { return $this->goodsCents + $this->deliveryCents; }
    public function record(string $id): array
    {
        return ['id' => $id, 'created_at' => gmdate('c'), 'contact' => ['name' => $this->name, 'email' => $this->email],
            'delivery' => ['id' => $this->deliveryId, 'title' => $this->deliveryTitle, 'address' => $this->address, 'price_cents' => $this->deliveryCents],
            'items' => $this->items, 'goods_cents' => $this->goodsCents, 'total_cents' => $this->total()];
    }
}
