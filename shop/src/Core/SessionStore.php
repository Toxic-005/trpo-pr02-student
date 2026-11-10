<?php
declare(strict_types=1);
namespace Shop\Core;
final class SessionStore
{
    private array $state;
    public function __construct(array &$state) { $this->state =& $state; }
    public function get(string $key, mixed $default = null): mixed { return $this->state[$key] ?? $default; }
    public function set(string $key, mixed $value): void { $this->state[$key] = $value; }
    public function forget(string $key): void { unset($this->state[$key]); }
    public function token(): string
    {
        if (!is_string($this->get('csrf'))) { $this->set('csrf', bin2hex(random_bytes(32))); }
        return $this->get('csrf');
    }
    public function verifyToken(string $token): void
    {
        if (!hash_equals($this->token(), $token)) { throw new HttpException(403, 'Форма устарела. Обновите страницу.'); }
    }
    public function flash(string $message): void { $this->set('flash', $message); }
    public function takeFlash(): ?string
    {
        $value = $this->get('flash'); $this->forget('flash');
        return is_string($value) ? $value : null;
    }
}
