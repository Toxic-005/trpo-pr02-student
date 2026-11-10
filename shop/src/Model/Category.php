<?php
declare(strict_types=1);
namespace Shop\Model;
final class Category
{
    public function __construct(public readonly int $id, public readonly string $name)
    {
        if ($id <= 0 || trim($name) === '') { throw new \InvalidArgumentException('Некорректная категория.'); }
    }
}
