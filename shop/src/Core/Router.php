<?php
declare(strict_types=1);
namespace Shop\Core;

final class Router
{
    private array $routes = [];
    public function __construct(private $notFound) {}
    public function add(string $method, string $path, callable $handler): void
    {
        $pattern = '#^' . str_replace('\\{id\\}', '([^/]+)', preg_quote($path, '#')) . '$#D';
        $this->routes[] = [$method, $pattern, $handler];
    }
    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as [$method, $pattern, $handler]) {
            if (!preg_match($pattern, $request->path, $matches)) { continue; }
            if ($request->method !== $method) { $allowed[] = $method; continue; }
            array_shift($matches);
            return $handler($request, ...$matches);
        }
        if ($allowed) {
            return new Response('Метод не поддерживается.', 405, ['Allow' => implode(', ', array_unique($allowed))]);
        }
        return ($this->notFound)($request);
    }
}
