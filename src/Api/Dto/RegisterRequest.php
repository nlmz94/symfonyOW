<?php

namespace App\Api\Dto;

use App\Validator\Constraints\StrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Please enter your email')]
        #[Assert\Email(message: 'Please enter a valid email address')]
        #[Assert\Length(max: 255)]
        public readonly string $email = '',

        #[Assert\NotBlank(message: 'Please enter a password')]
        #[StrongPassword]
        public readonly string $password = '',
    ) {
    }
}
