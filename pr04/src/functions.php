<?php
declare(strict_types=1);

function e(string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function normalizeEmployee(array $row): array
{
    if (count($row) !== 5 || !ctype_digit($row[0]) || !ctype_digit($row[3])) {
        throw new RuntimeException('Некорректная строка employees.csv.');
    }
    return ['id' => (int) $row[0], 'name' => trim($row[1]),
        'department' => trim($row[2]), 'experience' => (int) $row[3], 'email' => trim($row[4])];
}

function loadEmployees(string $path): array
{
    $handle = @fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Не удалось прочитать список сотрудников.');
    }
    try {
        $header = fgetcsv($handle, 0, ';', '"', '');
        if (is_array($header) && is_string($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        if ($header !== ['id', 'name', 'department', 'experience', 'email']) {
            throw new RuntimeException('Неверные столбцы employees.csv.');
        }
        $employees = [];
        while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            if ($row === [null]) {
                continue;
            }
            $employees[] = normalizeEmployee($row);
        }
        return $employees;
    } finally {
        fclose($handle);
    }
}

function normalizeInput(array $source): array
{
    $values = [];
    foreach (['name', 'email', 'department', 'experience'] as $field) {
        $values[$field] = input($source, $field);
    }
    return $values;
}

function validateInput(array $values, array $departments): array
{
    $errors = [];
    $length = preg_match_all('/./us', $values['name']);
    if ($values['name'] === '' || $length === false || $length > 100) {
        $errors['name'] = 'Введите имя длиной от 1 до 100 символов.';
    }
    if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email.';
    }
    if (!in_array($values['department'], $departments, true)) {
        $errors['department'] = 'Выберите подразделение из списка.';
    }
    $experience = filter_var($values['experience'], FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0, 'max_range' => 60]]);
    if ($experience === false) {
        $errors['experience'] = 'Стаж должен быть целым числом от 0 до 60 лет.';
    }
    return $errors;
}

function filterEmployees(array $employees, string $department): array
{
    return $department === '' ? $employees : array_values(array_filter($employees,
        static fn(array $employee): bool => $employee['department'] === $department));
}
