<?php
declare(strict_types=1);
namespace Shop\Core;

final class JsonFile
{
    public static function read(string $path): mixed
    {
        $raw = @file_get_contents($path);
        if ($raw === false) { throw new \RuntimeException('Не удалось прочитать файл данных.'); }
        try { return json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw new \RuntimeException('Повреждён JSON данных.', 0, $exception); }
    }
    public static function object(string $path): array
    {
        $data = self::read($path);
        if (!is_array($data) || array_is_list($data)) { throw new \RuntimeException('Ожидается объект JSON.'); }
        return $data;
    }
    public static function list(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false) { throw new \RuntimeException('Не удалось прочитать файл данных.'); }
        try { $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $exception) { throw new \RuntimeException('Повреждён JSON данных.', 0, $exception); }
        if (!str_starts_with(ltrim($raw), '[') || !is_array($data) || !array_is_list($data)) {
            throw new \RuntimeException('Ожидается список JSON.');
        }
        return $data;
    }
}
