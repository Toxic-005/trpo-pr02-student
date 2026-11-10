<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/functions.php';
set_error_handler(static function (int $severity, string $message): never { throw new ErrorException($message, 0, $severity); });
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
    echo 'PASS: ' . $label . PHP_EOL;
}
$employees = loadEmployees(__DIR__ . '/../data/employees.csv');
$departments = array_values(array_unique(array_column($employees, 'department')));
check(count($employees) === 5, 'Все пять строк CSV прочитаны');
check(count(filterEmployees($employees, 'Разработка')) === 2, 'GET-фильтр подразделения');
check(count(filterEmployees($employees, '')) === 5, 'Без фильтра видны все записи');
check(filterEmployees($employees, 'Нет') === [], 'Пустой результат фильтра');
$cases = json_decode(file_get_contents(__DIR__ . '/../data/form_cases.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($cases as $case) {
    $values = normalizeInput(['name' => $case['name'], 'experience' => (string) $case['experience'],
        'email' => 'student@example.test', 'department' => 'Разработка']);
    check((validateInput($values, $departments) === []) === $case['valid'], $case['case']);
}
$valid = ['name' => 'Иван', 'email' => 'ivan@example.test', 'experience' => '0', 'department' => 'Разработка'];
check(validateInput($valid, $departments) === [], 'Нулевой стаж допустим');
foreach (['-1', '61', '1.5', 'abc', ''] as $value) {
    check(isset(validateInput(array_replace($valid, ['experience' => $value]), $departments)['experience']), 'Недопустимый стаж: ' . $value);
}
check(isset(validateInput(array_replace($valid, ['name' => str_repeat('Я', 101)]), $departments)['name']), 'Имя длиннее 100 символов');
check(isset(validateInput(normalizeInput(array_replace($valid, ['name' => '   '])), $departments)['name']), 'Пробелы нормализуются');
check(isset(validateInput(array_replace($valid, ['email' => 'bad']), $departments)['email']), 'Неверный email');
check(normalizeInput(['name' => ['x']])['name'] === '', 'Массив вместо строки не вызывает warning');
check(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'Безопасный вывод HTML');
echo "Checks: $checks\n";
