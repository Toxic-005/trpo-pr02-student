<?php
declare(strict_types=1);

function e(string|int $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function decodeList(string $raw): array
{
    try {
        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException('Файл данных содержит повреждённый JSON.', 0, $exception);
    }
    if (!str_starts_with(ltrim($raw), '[') || !is_array($rows) || !array_is_list($rows)) {
        throw new RuntimeException('В файле данных ожидается JSON-список.');
    }
    return $rows;
}

function validateNotes(array $notes): array
{
    $ids = [];
    foreach ($notes as $note) {
        if (!is_array($note) || !is_int($note['id'] ?? null) || $note['id'] <= 0
            || !is_string($note['title'] ?? null) || trim($note['title']) === ''
            || !is_bool($note['done'] ?? null) || isset($ids[$note['id']])) {
            throw new RuntimeException('Некорректная структура заметок в JSON.');
        }
        $ids[$note['id']] = true;
    }
    return $notes;
}

function loadNotes(string $path): array
{
    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Не удалось открыть notes.json. Восстановите файл из Git или проверьте права.');
    }
    try {
        if (!flock($handle, LOCK_SH)) { throw new RuntimeException('Не удалось заблокировать файл для чтения.'); }
        $raw = @file_get_contents($path);
        if ($raw === false) { throw new RuntimeException('Не удалось прочитать заметки.'); }
        return validateNotes(decodeList($raw));
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

/** Блокировка охватывает чтение, выделение id и запись: параллельные добавления не теряются. */
function saveNotes(string $path, callable $change): void
{
    $handle = @fopen($path, 'r+b');
    if ($handle === false) { throw new RuntimeException('Не удалось открыть notes.json для записи. Проверьте права.'); }
    try {
        if (!flock($handle, LOCK_EX)) { throw new RuntimeException('Не удалось заблокировать файл для записи.'); }
        $raw = stream_get_contents($handle);
        if ($raw === false) { throw new RuntimeException('Не удалось прочитать заметки перед записью.'); }
        $notes = validateNotes($change(validateNotes(decodeList($raw))));
        $encoded = json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
        if (!rewind($handle) || fwrite($handle, $encoded) !== strlen($encoded)
            || !ftruncate($handle, strlen($encoded)) || !fflush($handle)) {
            throw new RuntimeException('Не удалось полностью сохранить заметки. Проверьте свободное место.');
        }
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function addNote(string $path, string $title): void
{
    $title = trim($title);
    $length = preg_match_all('/./us', $title);
    if ($title === '' || $length === false || $length > 200) {
        throw new InvalidArgumentException('Введите текст заметки длиной от 1 до 200 символов.');
    }
    saveNotes($path, static function (array $notes) use ($title): array {
        $maximum = $notes ? max(array_column($notes, 'id')) : 0;
        if ($maximum === PHP_INT_MAX) { throw new RuntimeException('Исчерпан диапазон идентификаторов.'); }
        $notes[] = ['id' => $maximum + 1, 'title' => $title, 'done' => false];
        return $notes;
    });
}

function loadUsers(string $path): array
{
    $raw = @file_get_contents($path);
    if ($raw === false) { throw new RuntimeException('Не удалось прочитать users.json.'); }
    $users = decodeList($raw);
    $ids = [];
    foreach ($users as $user) {
        if (!is_array($user) || !is_int($user['id'] ?? null) || $user['id'] <= 0
            || !is_string($user['name'] ?? null) || trim($user['name']) === '' || isset($ids[$user['id']])) {
            throw new RuntimeException('Некорректный список пользователей.');
        }
        $ids[$user['id']] = true;
    }
    return $users;
}

function applySessionAction(array &$state, string $action, string $name, array $users): void
{
    if ($action === 'set_name') {
        if (!in_array($name, array_column($users, 'name'), true)) {
            throw new InvalidArgumentException('Выберите пользователя из списка.');
        }
        $state['display_name'] = $name;
    } elseif ($action === 'clear') {
        unset($state['display_name'], $state['favorites']);
    } else {
        throw new InvalidArgumentException('Неизвестное действие сессии.');
    }
}

function displayName(array $state): string { return $state['display_name'] ?? 'Гость'; }

function render(string $template, array $data): void
{
    extract($data, EXTR_SKIP);
    require $template;
}
