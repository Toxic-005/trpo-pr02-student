<?php
declare(strict_types=1);
namespace Shop\Core;
final class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message) { parent::__construct($message); }
}
