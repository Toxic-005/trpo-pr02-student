<?php
declare(strict_types=1);
// Каждый набор выполняется отдельно: session_start должен предшествовать выводу.
$total = 0;
foreach (['catalog','cart','order','security'] as $test) {
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/'.$test.'.php');
    passthru($command,$status);
    if ($status !== 0) { exit($status); }
    $total++;
}
echo "Suites passed: $total\n";
