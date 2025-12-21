<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class StrongPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        // Check minimum length
        if (strlen($value) < 12) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
            return;
        }

        // Check for at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}