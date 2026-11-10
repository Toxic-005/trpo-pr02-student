<?php
declare(strict_types=1);
namespace Shop\Core;
use Shop\Controller\ErrorController;

final class Application
{
    public function __construct(private Router $router, private ErrorController $errors) {}
    public function handle(Request $request): Response
    {
        try { return $this->router->dispatch($request); }
        catch (HttpException $exception) { return $this->errors->show($exception->status, $exception->getMessage()); }
        catch (\Throwable $exception) {
            error_log('Shop: ' . get_class($exception));
            return $this->errors->show(500, getenv('APP_ENV') === 'development'
                ? $exception->getMessage() : 'Не удалось выполнить запрос. Попробуйте позже.');
        }
    }
}
