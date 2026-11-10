<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/functions.php';
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
error_reporting(E_ALL);
$version = '1.0';
$maxExperience = 60;
$showTable = true;
$employees = [];
$departments = [];
$errors = [];
$success = null;
$dataError = null;
$values = normalizeInput($_POST);
$filter = input($_GET, 'department');
try {
    $employees = loadEmployees(__DIR__ . '/../data/employees.csv');
    $departments = array_values(array_unique(array_column($employees, 'department')));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $errors = validateInput($values, $departments);
        if (!$errors) {
            $success = 'Заявка сотрудника ' . $values['name'] . ' принята для проверки.';
        }
    }
} catch (RuntimeException $exception) {
    http_response_code(503);
    $dataError = $exception->getMessage();
}
$visibleEmployees = filterEmployees($employees, $filter);
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Заявки сотрудников · Практическая работа 4</title><link rel="stylesheet" href="styles.css"></head>
<body><header><span class="eyebrow">ТРПО / Практическая работа 04</span><h1>Заявки сотрудников</h1>
<p>Версия <?= e($version) ?> · <?= e(date('d.m.Y')) ?> · Серверная обработка формы</p></header><main>
<?php if ($dataError !== null): ?><p class="error" role="alert"><?= e($dataError) ?></p><?php else: ?>
<div class="columns"><section class="panel" aria-labelledby="form-title"><h2 id="form-title">Новая заявка</h2>
<p>Проверьте данные перед отправкой. Заявка подтверждается в ответе сервера и не изменяет исходный CSV.</p>
<?php if ($success !== null): ?><p class="success" role="status"><?= e($success) ?></p><?php endif ?>
<?php if ($errors): ?><div class="error" role="alert"><strong>Исправьте данные</strong><ul><?php foreach ($errors as $message): ?><li><?= e($message) ?></li><?php endforeach ?></ul></div><?php endif ?>
<form method="post" action="index.php" novalidate>
<?php foreach (['name' => 'Имя сотрудника', 'email' => 'Email', 'experience' => 'Стаж, лет'] as $field => $label): ?>
<label for="<?= e($field) ?>"><?= e($label) ?></label>
<input id="<?= e($field) ?>" name="<?= e($field) ?>" type="<?= $field === 'experience' ? 'number' : ($field === 'email' ? 'email' : 'text') ?>" value="<?= e($values[$field]) ?>" required <?= $field === 'experience' ? 'min="0" max="' . e($maxExperience) . '" step="1"' : 'maxlength="' . ($field === 'name' ? '100' : '254') . '"' ?> <?= isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="error-' . e($field) . '"' : '' ?>>
<?php if (isset($errors[$field])): ?><small class="field-error" id="error-<?= e($field) ?>"><?= e($errors[$field]) ?></small><?php endif ?>
<?php endforeach ?>
<label for="department">Подразделение</label><select id="department" name="department" required <?= isset($errors['department']) ? 'aria-invalid="true" aria-describedby="error-department"' : '' ?>><option value="">Выберите подразделение</option>
<?php if ($values['department'] !== '' && !in_array($values['department'], $departments, true)): ?><option selected value="<?= e($values['department']) ?>"><?= e($values['department']) ?> (нет в списке)</option><?php endif ?>
<?php foreach ($departments as $department): ?><option <?= $values['department'] === $department ? 'selected' : '' ?>><?= e($department) ?></option><?php endforeach ?></select>
<?php if (isset($errors['department'])): ?><small class="field-error" id="error-department"><?= e($errors['department']) ?></small><?php endif ?>
<button type="submit">Отправить заявку</button></form></section>
<section class="panel" aria-labelledby="employees-title"><h2 id="employees-title">Сотрудники</h2><p class="muted">Найдено: <?= count($visibleEmployees) ?> из <?= count($employees) ?></p>
<form method="get" action="index.php"><label for="filter">Фильтр по подразделению</label><select id="filter" name="department"><option value="">Все подразделения</option>
<?php foreach ($departments as $department): ?><option <?= $filter === $department ? 'selected' : '' ?>><?= e($department) ?></option><?php endforeach ?></select><button>Показать</button> <a href="index.php">Сбросить</a></form>
<?php if (!$visibleEmployees): ?><p class="empty">Сотрудников в этом подразделении нет.</p><?php elseif ($showTable): ?>
<div class="table-wrap"><table><caption>Данные из employees.csv</caption><thead><tr><th>ID</th><th>Имя</th><th>Подразделение</th><th>Стаж</th><th>Email</th></tr></thead><tbody>
<?php foreach ($visibleEmployees as $employee): ?><tr><?php foreach ($employee as $value): ?><td><?= e($value) ?></td><?php endforeach ?></tr><?php endforeach ?>
</tbody></table></div><?php endif ?></section></div><?php endif ?></main><footer>Практическая работа 4 · PHP, CSV, GET и POST</footer></body></html>
