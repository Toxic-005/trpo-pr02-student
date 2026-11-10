<?php
declare(strict_types=1);
namespace Shop\Controller;
use Shop\Core\{Request, Response, View, HttpException, SessionStore};
use Shop\Service\{CartService, CheckoutService, OrderValidator};
final class CheckoutController
{
    public function __construct(private View $view, private SessionStore $session, private CartService $cart,
        private CheckoutService $checkout, private OrderValidator $validator) {}
    public function index(Request $request): Response { return $this->form($this->validator->normalize([]), []); }
    private function form(array $values, array $errors, int $status = 200): Response
    {
        return $this->view->render('checkout', ['title' => 'Оформление заказа', 'values' => $values, 'errors' => $errors,
            'methods' => $this->validator->methods(), 'summary' => $this->cart->summary($this->session->get('cart', []))], $status);
    }
    public function create(Request $request): Response
    {
        $this->session->verifyToken($request->input('csrf'));
        $input = [];
        foreach (['name','email','address','delivery'] as $field) { $input[$field] = $request->input($field); }
        $values = $this->validator->normalize($input);
        $errors = $this->validator->validate($values);
        if ($errors) { return $this->form($values, $errors, 422); }
        $receipt = $this->checkout->place($values, $this->session->get('cart', []));
        $this->session->set('cart', []);
        $receipts = $this->session->get('receipts', []);
        $receipts[$receipt['id']] = $receipt;
        $this->session->set('receipts', array_slice($receipts, -10, null, true));
        $this->session->flash('Заказ сохранён. Спасибо!');
        return Response::redirect('/order/success?id=' . $receipt['id']);
    }
    public function success(Request $request): Response
    {
        $id = $request->query('id');
        $receipts = $this->session->get('receipts', []);
        if (!isset($receipts[$id])) { throw new HttpException(404, 'Подтверждение заказа недоступно в этой сессии.'); }
        return $this->view->render('success', ['title' => 'Заказ оформлен', 'receipt' => $receipts[$id]]);
    }
}
