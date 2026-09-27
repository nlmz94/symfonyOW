<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
class StrongPassword extends Constraint
{
    public string $message = 'Password must be at least 12 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.';

    public function validatedBy(): string
    {
        return static::class . 'Validator';
    }
}
