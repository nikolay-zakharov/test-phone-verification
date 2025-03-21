<?php

namespace App\Request\API\PhoneVerification;

use Symfony\Component\Validator\Constraints as Assert;

readonly class RequestCodeDto
{
    public function __construct(
        #[Assert\Regex(pattern: '/^\+\d{11}$/', message: 'Invalid phone number (expected format +19876543210)')]
        #[Assert\NotNull(message: 'Required')]
        public ?string $phone,
    ) {
    }
}
