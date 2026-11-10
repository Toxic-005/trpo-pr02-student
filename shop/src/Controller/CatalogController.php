<?php
declare(strict_types=1);
namespace Shop\Controller;
use Shop\Core\{Request, Response, View};
final class CatalogController
{
    public function __construct(private View $view) {}
    public function index(Request $request): Response
    {
        $products = [['name' => 'Учебная книга', 'price' => 90000], ['name' => 'Учебный курс', 'price' => 250000]];
        return $this->view->render('catalog', ['title' => 'Каталог', 'products' => $products]);
    }
}
