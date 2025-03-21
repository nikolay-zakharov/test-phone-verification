<?php

namespace App\Request\API\PhoneVerification;

use Symfony\Component\Validator\Constraints as Assert;

readonly class VerifyDto
{
    public function __construct(
        #[Assert\Regex(pattern: '/^\+\d{11}$/', message: 'Invalid phone number (expected format: +19876543210)')]
        #[Assert\NotNull(message: 'required')]
        public ?string $phone,

        #[Assert\Regex(pattern: '/^\d{4}$/', message: 'Invalid code. 4 digits expected')]
        #[Assert\NotNull(message: 'Required')]
        public ?string $code,
    ) {
    }
}
