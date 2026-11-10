<?php
declare(strict_types=1);
namespace Shop\Service;
use Shop\Core\HttpException;
use Shop\Model\OrderData;
use Shop\Repository\OrderRepository;
final class CheckoutService
{
    public function __construct(private CartService $cart, private OrderValidator $validator, private OrderRepository $orders) {}
    public function place(array $values, array $cartState): array
    {
        if ($this->validator->validate($values)) { throw new HttpException(422, 'Исправьте данные заказа.'); }
        $summary = $this->cart->summary($cartState);
        if (!$summary['items']) { throw new HttpException(409, 'Добавьте товары в корзину перед оформлением.'); }
        $method = $this->validator->method($values['delivery']);
        $items = array_map(static fn(array $line): array => ['product_id' => $line['product']->id,
            'name' => $line['product']->name, 'price_cents' => $line['product']->priceCents,
            'qty' => $line['quantity'], 'subtotal_cents' => $line['subtotal']], $summary['items']);
        $order = new OrderData($values['name'], $values['email'], $method['id'] === 'pickup' ? 'Пункт выдачи учебного магазина' : $values['address'],
            $method['id'], $method['title'], $method['price_cents'], $items, $summary['total']);
        $id = $this->orders->save($order);
        return ['id' => $id, 'total_cents' => $order->total(), 'delivery' => $method['title']];
    }
}
