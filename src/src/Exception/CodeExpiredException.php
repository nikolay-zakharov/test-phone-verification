<?php

namespace App\Exception;

class CodeExpiredException extends \RuntimeException
{
    protected $message = 'Verification code expired. Request another one';
}
