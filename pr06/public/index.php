<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/functions.php';
ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
error_reporting(E_ALL);
session_name('trpo_pr06');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$page = input($_GET, 'page');
if ($page !== '' && $page !== 'list') {
    http_response_code(404);
    render(__DIR__ . '/../templates/404.php', []);
    exit;
}
$path = __DIR__ . '/../data/notes.json';
$notes = [];
$users = [];
$error = null;
$storageError = null;
$title = input($_POST, 'title');
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
try {
    $users = loadUsers(__DIR__ . '/../data/users.json');
    $notes = loadNotes($path);
} catch (RuntimeException $exception) {
    $storageError = $exception->getMessage();
    http_response_code(503);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf'], input($_POST, 'csrf'))) {
            http_response_code(403);
            throw new InvalidArgumentException('Форма устарела. Обновите страницу и повторите действие.');
        }
        $action = input($_POST, 'action');
        if ($action === 'add') {
            if ($storageError !== null) { throw new RuntimeException($storageError); }
            addNote($path, $title);
            $_SESSION['flash'] = 'Заметка добавлена.';
        } elseif ($action === 'set_name' || $action === 'clear') {
            applySessionAction($_SESSION, $action, input($_POST, 'name'), $users);
            session_regenerate_id(true);
            $_SESSION['flash'] = $action === 'clear' ? 'Состояние сессии очищено.' : 'Пользователь сохранён в сессии.';
        } elseif ($action === 'favorite') {
            $id = filter_var(input($_POST, 'id'), FILTER_VALIDATE_INT);
            if ($id === false || !in_array($id, array_column($notes, 'id'), true)) {
                throw new InvalidArgumentException('Заметка не найдена.');
            }
            $_SESSION['favorites'] ??= [];
            if (in_array($id, $_SESSION['favorites'], true)) {
                $_SESSION['favorites'] = array_values(array_diff($_SESSION['favorites'], [$id]));
            } else {
                $_SESSION['favorites'][] = $id;
            }
        } else {
            throw new InvalidArgumentException('Неизвестное действие.');
        }
        header('Location: index.php?page=list', true, 303);
        exit;
    } catch (InvalidArgumentException $exception) {
        if (http_response_code() !== 403) { http_response_code(422); }
        $error = $exception->getMessage();
    } catch (RuntimeException $exception) {
        http_response_code(503);
        $error = $exception->getMessage();
    }
}
render(__DIR__ . '/../templates/list.php', [
    'notes' => $notes, 'users' => $users, 'name' => displayName($_SESSION),
    'csrf' => $_SESSION['csrf'], 'favorites' => $_SESSION['favorites'] ?? [],
    'title' => $title, 'error' => $error, 'storageError' => $storageError, 'flash' => $flash,
]);
