<?php

namespace App\Tests\Validator;

use App\Validator\Constraints\StrongPassword;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StrongPasswordValidatorTest extends KernelTestCase
{
    public function testPasswordValidation(): void
    {
        self::bootKernel();
        $validator = self::getContainer()->get('validator');

        // Test valid password
        $errors = $validator->validate('ValidPassword123!', new StrongPassword());
        $this->assertCount(0, $errors, 'Valid password should not have errors');

        // Test too short password
        $errors = $validator->validate('Short1!', new StrongPassword());
        $this->assertCount(1, $errors, 'Short password should have 1 error');

        // Test missing uppercase
        $errors = $validator->validate('validpassword123!', new StrongPassword());
        $this->assertCount(1, $errors, 'Password without uppercase should have 1 error');

        // Test missing lowercase
        $errors = $validator->validate('VALIDPASSWORD123!', new StrongPassword());
        $this->assertCount(1, $errors, 'Password without lowercase should have 1 error');

        // Test missing number
        $errors = $validator->validate('ValidPassword!', new StrongPassword());
        $this->assertCount(1, $errors, 'Password without number should have 1 error');

        // Test missing special character
        $errors = $validator->validate('ValidPassword123', new StrongPassword());
        $this->assertCount(1, $errors, 'Password without special character should have 1 error');

        // Test empty password (should not error - handled by NotBlank)
        $errors = $validator->validate('', new StrongPassword());
        $this->assertCount(0, $errors, 'Empty password should not have strong password errors');

        // Test null password (should not error - handled by NotBlank)
        $errors = $validator->validate(null, new StrongPassword());
        $this->assertCount(0, $errors, 'Null password should not have strong password errors');
    }
}