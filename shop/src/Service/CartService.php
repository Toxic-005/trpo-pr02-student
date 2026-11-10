<?php
declare(strict_types=1);
namespace Shop\Service;
use Shop\Core\HttpException;
use Shop\Repository\ProductRepository;
final class CartService
{
    public function __construct(private ProductRepository $products) {}
    public function change(array $cart, string $action, int $id, int $quantity = 1): array
    {
        $product = $this->products->find($id);
        if ($product === null) { throw new HttpException(404, 'Товар не найден.'); }
        if ($action === 'remove') { unset($cart[$id]); return $cart; }
        if (!in_array($action, ['add', 'set'], true)) { throw new \InvalidArgumentException('Неизвестное действие корзины.'); }
        if ($quantity < 1 || $quantity > 99) { throw new HttpException(422, 'Количество должно быть от 1 до 99.'); }
        $newQuantity = $action === 'add' ? ($cart[$id] ?? 0) + $quantity : $quantity;
        if ($newQuantity > min(99, $product->stock)) { throw new HttpException(422, 'Недостаточно товара на складе.'); }
        $cart[$id] = $newQuantity;
        return $cart;
    }
    public function summary(array $cart): array
    {
        $items = []; $total = 0; $count = 0;
        foreach ($cart as $id => $quantity) {
            if (!is_int($quantity) || $quantity < 1 || $quantity > 99) { throw new HttpException(409, 'Корзина повреждена. Обновите её состав.'); }
            $product = $this->products->find((int)$id);
            if ($product === null || $product->stock < $quantity) { throw new HttpException(409, 'Состав или остатки изменились. Удалите недоступную позицию.'); }
            $subtotal = $product->priceCents * $quantity;
            $items[] = ['product' => $product, 'quantity' => $quantity, 'subtotal' => $subtotal];
            $count += $quantity; $total += $subtotal;
        }
        return ['items' => $items, 'count' => $count, 'total' => $total];
    }
}
