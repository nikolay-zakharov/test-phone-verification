<?php

namespace App\Exception;

class MethodLockedException extends \RuntimeException
{
    public function __construct(private readonly int $seconds, string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getSeconds(): int
    {
        return $this->seconds;
    }
}