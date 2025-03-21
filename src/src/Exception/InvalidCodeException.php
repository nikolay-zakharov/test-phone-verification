<?php

namespace App\Exception;

class InvalidCodeException extends \RuntimeException
{
    protected $message = 'Invalid code';
}
