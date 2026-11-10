<?php
declare(strict_types=1);
namespace Shop\Core;

final class Request
{
    public function __construct(public readonly string $method, public readonly string $path,
        private array $query = [], private array $post = []) {}

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), is_string($path) ? $path : '/', $_GET, $_POST);
    }

    public function query(string $key, string $default = ''): string { return $this->scalar($this->query, $key, $default); }
    public function input(string $key, string $default = ''): string { return $this->scalar($this->post, $key, $default); }
    private function scalar(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value)) { throw new HttpException(400, 'Параметр должен быть строкой: ' . $key); }
        return trim($value);
    }
}
