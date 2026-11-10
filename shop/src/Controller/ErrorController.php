<?php
declare(strict_types=1);
namespace Shop\Controller;
use Shop\Core\{Request, Response, View};
final class ErrorController
{
    public function __construct(private View $view) {}
    public function notFound(Request $request): Response { return $this->show(404, 'Страница или товар не найдены.'); }
    public function show(int $status, string $message): Response
    {
        return $this->view->render('error', ['title' => 'Ошибка ' . $status, 'status' => $status, 'message' => $message], $status);
    }
}
