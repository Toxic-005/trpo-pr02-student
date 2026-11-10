<?php
declare(strict_types=1);
namespace Shop\Core;

final class Response
{
    public function __construct(public readonly string $body = '', public readonly int $status = 200,
        public readonly array $headers = []) {}
    public static function redirect(string $path): self { return new self('', 302, ['Location' => $path]); }
    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Cache-Control: no-store');
        header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self'; img-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
        foreach ($this->headers as $name => $value) { header($name . ': ' . $value); }
        echo $this->body;
    }
}
