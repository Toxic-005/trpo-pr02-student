<?php
declare(strict_types=1);
namespace Shop\Controller;
use Shop\Core\{Request, Response, View, SessionStore, HttpException};
use Shop\Service\CartService;
final class CartController
{
    public function __construct(private View $view, private CartService $cart, private SessionStore $session) {}
    private function state(): array
    {
        $state = $this->session->get('cart', []);
        if (!is_array($state)) { throw new HttpException(409, 'Состояние корзины повреждено.'); }
        return $state;
    }
    public function index(Request $request): Response
    {
        return $this->view->render('cart', ['title' => 'Корзина', 'summary' => $this->cart->summary($this->state())]);
    }
    public function add(Request $request): Response { return $this->update($request, 'add'); }
    public function set(Request $request): Response { return $this->update($request, 'set'); }
    public function remove(Request $request): Response { return $this->update($request, 'remove'); }
    private function update(Request $request, string $action): Response
    {
        $this->session->verifyToken($request->input('csrf'));
        $id = filter_var($request->input('product_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $quantity = filter_var($request->input('qty', '1'), FILTER_VALIDATE_INT);
        if ($id === false) { throw new HttpException(404, 'Товар не найден.'); }
        if ($quantity === false) { throw new HttpException(422, 'Количество должно быть целым числом.'); }
        $this->session->set('cart', $this->cart->change($this->state(), $action, $id, $quantity));
        $this->session->flash(match ($action) {'add' => 'Товар добавлен в корзину.', 'set' => 'Количество обновлено.', default => 'Товар удалён из корзины.'});
        return Response::redirect('/cart');
    }
}
