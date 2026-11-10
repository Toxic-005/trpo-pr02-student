<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/functions.php';
set_error_handler(static function (int $severity, string $message): bool {
    if (!(error_reporting() & $severity)) { return false; }
    throw new ErrorException($message, 0, $severity);
});
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
    echo 'PASS: ' . $label . PHP_EOL;
}
$users = loadUsers(__DIR__ . '/../data/users.json');
$scenarios = decodeList(file_get_contents(__DIR__ . '/../data/session_scenarios.json'));
$state = [];
foreach ($scenarios as $scenario) {
    switch ($scenario['scenario']) {
        case 'new_session': check(displayName($state) === 'Гость', $scenario['expected']); break;
        case 'set_name':
            applySessionAction($state, 'set_name', $scenario['value'], $users);
            check(displayName($state) === $scenario['value'], $scenario['expected']); break;
        case 'clear':
            applySessionAction($state, 'clear', '', $users);
            check(displayName($state) === 'Гость', $scenario['expected']); break;
        default: throw new RuntimeException('Неизвестный сценарий dataset.');
    }
}
$path = tempnam(sys_get_temp_dir(), 'trpo06_');
try {
    copy(__DIR__ . '/../data/notes.json', $path);
    check(count(loadNotes($path)) === 2, 'Исходные заметки');
    addNote($path, ' Новая заметка ');
    addNote($path, 'Ещё одна');
    $notes = loadNotes($path);
    check(count($notes) === 4 && $notes[2]['id'] === 3 && $notes[3]['id'] === 4, 'Сохранение и уникальные id');
    check($notes[2]['title'] === 'Новая заметка' && !$notes[2]['done'], 'Нормализация новой записи');
    foreach (['   ', str_repeat('Я', 201)] as $title) {
        try { addNote($path, $title); throw new RuntimeException('Принят неверный текст'); }
        catch (InvalidArgumentException) { check(true, 'Неверный текст отклонён'); }
    }
    foreach (['{broken', '{}', '[{"id":1}]'] as $badJson) {
        file_put_contents($path, $badJson);
        try { loadNotes($path); throw new LogicException('Принят неверный JSON'); }
        catch (RuntimeException) { check(true, 'Повреждённые данные обработаны'); }
    }
    file_put_contents($path, '[]');
    addNote($path, 'Первая');
    check(loadNotes($path)[0]['id'] === 1, 'Добавление в пустой список');
    unlink($path);
    try { loadNotes($path); throw new LogicException('Отсутствие файла не обработано'); }
    catch (RuntimeException) { check(true, 'Отсутствующий файл обработан'); }
} finally {
    if (file_exists($path)) { unlink($path); }
}
check(displayName([]) === 'Гость', 'Новая независимая сессия');
check(e('<script>') === '&lt;script&gt;', 'Экранирование заметок');
echo "Checks: $checks\n";
