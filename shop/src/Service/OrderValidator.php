<?php
declare(strict_types=1);
namespace Shop\Service;
final class OrderValidator
{
    private array $methods = [];
    public function __construct(array $methods)
    {
        foreach ($methods as $row) {
            if (!is_array($row) || !is_string($row['id'] ?? null) || !is_string($row['title'] ?? null)
                || !in_array($row['id'], ['pickup','courier'], true) || trim($row['title']) === ''
                || !(is_int($row['price'] ?? null) || is_float($row['price'] ?? null)) || !is_finite((float)$row['price'])
                || $row['price'] < 0 || $row['price'] > 100000 || isset($this->methods[$row['id']])) {
                throw new \RuntimeException('Некорректные способы доставки.');
            }
            $this->methods[$row['id']] = ['id' => $row['id'], 'title' => $row['title'], 'price_cents' => (int)round($row['price']*100)];
        }
        if (!$this->methods) { throw new \RuntimeException('Способы доставки отсутствуют.'); }
    }
    public function methods(): array { return array_values($this->methods); }
    public function method(string $id): array
    {
        if (!isset($this->methods[$id])) { throw new \InvalidArgumentException('Неизвестная доставка.'); }
        return $this->methods[$id];
    }
    public function normalize(array $input): array
    {
        $values = [];
        foreach (['name','email','delivery','address'] as $field) {
            $value = $input[$field] ?? '';
            $values[$field] = is_string($value) ? trim($value) : '';
        }
        return $values;
    }
    public function validate(array $values): array
    {
        $errors = [];
        $nameLength = preg_match_all('/./us', $values['name']);
        if ($values['name'] === '' || $nameLength === false || $nameLength > 100) { $errors['name'] = 'Укажите имя длиной от 1 до 100 символов.'; }
        if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Укажите корректный email.'; }
        if (!isset($this->methods[$values['delivery']])) { $errors['delivery'] = 'Выберите доступный способ доставки.'; }
        $addressLength = preg_match_all('/./us', $values['address']);
        if ($addressLength === false || $addressLength > 200 || ($values['delivery'] === 'courier' && $values['address'] === '')) {
            $errors['address'] = 'Для курьера укажите адрес длиной от 1 до 200 символов.';
        }
        return $errors;
    }
}
