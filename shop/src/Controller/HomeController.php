<?php
declare(strict_types=1);
namespace Shop\Controller;
use Shop\Core\{Request, Response, View};
final class HomeController
{
    public function __construct(private View $view, private array $page) {}
    public function index(Request $request): Response
    {
        return $this->view->render('home', ['title' => $this->page['store_name'], 'page' => $this->page]);
    }
    public function about(Request $request): Response
    {
        return $this->view->render('about', ['title' => 'О магазине', 'page' => $this->page]);
    }
}
