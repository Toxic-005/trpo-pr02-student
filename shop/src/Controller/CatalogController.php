<?php
declare(strict_types=1);
namespace Shop\Controller;
use Shop\Core\{Request, Response, View, HttpException};
use Shop\Repository\ProductRepository;
final class CatalogController
{
    public function __construct(private View $view, private ProductRepository $products) {}
    public function index(Request $request): Response
    {
        $rawCategory = $request->query('category');
        $category = $rawCategory === '' ? null : filter_var($rawCategory, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $sort = $request->query('sort', 'name');
        if ($category === false || !in_array($sort, ['name', 'price', 'price_desc'], true)) {
            throw new HttpException(400, 'Некорректная категория или сортировка.');
        }
        return $this->view->render('catalog', ['title' => 'Каталог', 'products' => $this->products->filter($category, $sort),
            'categories' => $this->products->categories(), 'category' => $category, 'sort' => $sort]);
    }
    public function show(Request $request, string $id): Response
    {
        $parsed = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $product = $parsed === false ? null : $this->products->find($parsed);
        if ($product === null) { throw new HttpException(404, 'Товар не найден.'); }
        return $this->view->render('product', ['title' => $product->name, 'product' => $product]);
    }
}
