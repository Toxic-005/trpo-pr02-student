<?php
declare(strict_types=1);
// Только явно разрешённые статические ресурсы обходят единую точку входа.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (in_array($path, ['/styles.css', '/shop-logo.svg'], true) && is_file(__DIR__ . $path)) {
    return false;
}
require __DIR__ . '/index.php';
