<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
use Shop\Core\{JsonFile, HttpException, Request, Router, Response, View};
use Shop\Repository\ProductRepository;
use Shop\Service\CartService;
$checks = 0;
$check = static function(bool $ok, string $label) use (&$checks): void { if (!$ok) { throw new RuntimeException('FAIL: '.$label); } echo 'PASS: '.$label.PHP_EOL; $checks++; };
$repository = new ProductRepository(dirname(__DIR__).'/data/pr09/products.json');
$cart = new CartService($repository);
foreach (JsonFile::list(dirname(__DIR__).'/data/pr11/security_cases.json') as $case) {
    if ($case['expected'] === 'escaped') { $check(!str_contains(e($case['input']), '<script>') && str_contains(e($case['input']), '&lt;script&gt;'), 'security_cases: escaped'); }
    if ($case['expected'] === 'rejected') {
        try { $cart->change([], 'add', 1, $case['qty']); throw new LogicException('Количество принято'); }
        catch (HttpException $exception) { $check($exception->status === 422,'security_cases: rejected'); }
    }
    if ($case['expected'] === 'not_found') {
        try { $cart->change([], 'add', $case['product_id']); throw new LogicException('Товар принят'); }
        catch (HttpException $exception) { $check($exception->status === 404,'security_cases: not_found'); }
    }
}
$router = new Router(static fn(Request $r): Response => new Response('',404));
$router->add('POST','/cart/add',static fn(Request $r): Response => Response::redirect('/cart'));
$check($router->dispatch(new Request('GET','/cart/add'))->status === 405, 'GET не изменяет корзину');
$check($router->dispatch(new Request('GET','/unknown'))->status === 404, 'Централизованный 404');
$check($router->dispatch(new Request('POST','/cart/add'))->status === 302, 'Redirect 302');
try { (new Request('GET','/', ['category'=>['x']]))->query('category'); throw new LogicException('Принят массив'); }
catch (HttpException $exception) { $check($exception->status === 400,'Массив вместо строки'); }
$view = new View(dirname(__DIR__).'/views');
$html = $view->render('error',['title'=>'Ошибка','status'=>422,'message'=>'<script>alert(1)</script>'])->body;
$check(!str_contains($html,'<script>') && str_contains($html,'&lt;script&gt;'),'Экранирование настоящего шаблона');
$file = fopen(dirname(__DIR__).'/data/pr11/architecture_checklist.csv','rb');
fgetcsv($file,0,';','"','');
$evidence = [
 'Single entry point' => is_file(dirname(__DIR__).'/public/index.php'),
 'Router without HTML' => !str_contains(file_get_contents(dirname(__DIR__).'/src/Core/Router.php'),'<html'),
 'Controllers choose views' => str_contains(file_get_contents(dirname(__DIR__).'/src/Controller/CatalogController.php'),'->render('),
 'Models without HTTP globals' => true,
 'Views without data access' => true,
 'Namespaces' => class_exists(Shop\Model\Product::class),
 'Autoload' => str_contains(file_get_contents(dirname(__DIR__).'/bootstrap.php'),'spl_autoload_register'),
 '404 handler' => class_exists(Shop\Controller\ErrorController::class),
];
foreach (glob(dirname(__DIR__).'/src/Model/*.php') as $path) {
    $evidence['Models without HTTP globals'] = $evidence['Models without HTTP globals'] && !preg_match('/\$_(GET|POST|SESSION|SERVER)/',file_get_contents($path));
}
foreach (glob(dirname(__DIR__).'/views/*.php') as $path) {
    $evidence['Views without data access'] = $evidence['Views without data access'] && !preg_match('/file_get_contents|fopen\s*\(|JsonFile::|\$_(GET|POST|SESSION)/',file_get_contents($path));
}
while (($row=fgetcsv($file,0,';','"','')) !== false) { $check($evidence[$row[0]] ?? false,'architecture_checklist: '.$row[0]); }
fclose($file);
echo 'Checks: '.$checks.PHP_EOL;
