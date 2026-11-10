<?php
declare(strict_types=1);
function e(string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function money(int $cents): string { return number_format($cents / 100, 2, ',', ' ') . ' ₽'; }
