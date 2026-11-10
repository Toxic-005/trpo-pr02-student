<?php
declare(strict_types=1);
namespace Shop\Core;

final class View
{
    private ?\Closure $context = null;
    public function __construct(private string $directory) {}
    public function setContext(\Closure $context): void { $this->context = $context; }
    public function render(string $template, array $data = [], int $status = 200): Response
    {
        $shared = $this->context ? ($this->context)() : ['cartCount' => 0, 'csrf' => '', 'flash' => null];
        return new Response($this->capture('layout', array_merge($shared, $data,
            ['content' => $this->capture($template, array_merge($shared, $data))])), $status);
    }
    private function capture(string $template, array $data): string
    {
        if (!preg_match('/^[a-z_-]+$/D', $template)) { throw new \LogicException('Недопустимый шаблон.'); }
        extract($data, EXTR_SKIP);
        ob_start();
        try { require $this->directory . '/' . $template . '.php'; return (string) ob_get_clean(); }
        catch (\Throwable $exception) { ob_end_clean(); throw $exception; }
    }
}
