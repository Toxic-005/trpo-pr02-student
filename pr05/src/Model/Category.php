<?php
declare(strict_types=1);

final class Category
{
    public function __construct(private int $id, private string $name)
    {
        $this->name = trim($name);
        if ($id <= 0 || $this->name === '') {
            throw new InvalidArgumentException('Категория должна иметь положительный id и название.');
        }
    }

    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
}
