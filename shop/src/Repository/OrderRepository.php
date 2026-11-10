<?php
declare(strict_types=1);
namespace Shop\Repository;
use Shop\Core\JsonFile;
use Shop\Model\OrderData;
final class OrderRepository
{
    public function __construct(private string $directory, private string $seed) {}
    public function save(OrderData $order): string
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Не удалось подготовить хранилище заказов.');
        }
        $lock = @fopen($this->directory . '/orders.lock', 'c+b');
        if ($lock === false) { throw new \RuntimeException('Не удалось открыть блокировку заказов.'); }
        $temporary = null;
        try {
            if (!flock($lock, LOCK_EX)) { throw new \RuntimeException('Не удалось заблокировать хранилище.'); }
            $path = $this->directory . '/orders.json';
            $orders = JsonFile::list(is_file($path) ? $path : $this->seed);
            $ids = [];
            foreach ($orders as $saved) {
                if (!is_array($saved) || !is_string($saved['id'] ?? null) || !is_int($saved['total_cents'] ?? null)
                    || $saved['total_cents'] < 0 || !is_array($saved['items'] ?? null) || isset($ids[$saved['id']])) {
                    throw new \RuntimeException('Повреждена структура сохранённых заказов.');
                }
                $ids[$saved['id']] = true;
            }
            do { $id = bin2hex(random_bytes(16)); } while (isset($ids[$id]));
            $orders[] = $order->record($id);
            $encoded = json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
            $temporary = @tempnam($this->directory, 'order_');
            if ($temporary === false || @file_put_contents($temporary, $encoded) !== strlen($encoded) || !@rename($temporary, $path)) {
                throw new \RuntimeException('Заказ не сохранён. Проверьте права и свободное место.');
            }
            $temporary = null;
            return $id;
        } finally {
            if (is_string($temporary) && is_file($temporary)) { @unlink($temporary); }
            flock($lock, LOCK_UN); fclose($lock);
        }
    }
}
