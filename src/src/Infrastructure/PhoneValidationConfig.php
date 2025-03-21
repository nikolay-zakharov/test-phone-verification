<?php

namespace App\Infrastructure;

class PhoneValidationConfig
{
    public private(set) int $codeLifetime;
    public private(set) string $banTime;

    public function __construct(int $codeLifetime, string $banTime) {
        $this->codeLifetime = $codeLifetime;
        $this->banTime = $banTime;
    }
}
