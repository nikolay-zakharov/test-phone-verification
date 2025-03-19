<?php

namespace App\Request\API\PhoneVerification;

use Symfony\Component\Validator\Constraints as Assert;

class RequestCodeDto
{
    public function __construct(
        #[Assert\Regex(pattern: '/^\+\d{11}$/', message: 'Invalid phone number (expected format +19876543210)')]
        #[Assert\NotNull(message: 'Required')]
        public readonly ?string $phone,
    ) {
    }
}