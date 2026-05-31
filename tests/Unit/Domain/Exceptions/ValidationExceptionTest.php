<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Exceptions;

use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use DomainException;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $exception = new ValidationException([]);

        self::assertInstanceOf(DomainException::class, $exception);
    }

    public function test_errors_returns_the_injected_errors_array(): void
    {
        $errors = ['email' => ['The email field is required.']];
        $exception = new ValidationException($errors);

        self::assertSame($errors, $exception->errors());
    }

    public function test_get_message_returns_default_message(): void
    {
        $exception = new ValidationException([]);

        self::assertSame('Validation failed.', $exception->getMessage());
    }

    public function test_get_message_returns_custom_message(): void
    {
        $exception = new ValidationException([], 'Custom validation error.');

        self::assertSame('Custom validation error.', $exception->getMessage());
    }

    public function test_errors_returns_multiple_field_errors(): void
    {
        $errors = [
            'name' => ['The name field is required.'],
            'email' => ['The email field is required.', 'The email must be a valid email address.'],
        ];

        $exception = new ValidationException($errors);

        self::assertSame($errors, $exception->errors());
        self::assertCount(2, $exception->errors());
    }

    public function test_errors_returns_empty_array_when_no_errors(): void
    {
        $exception = new ValidationException([]);

        self::assertSame([], $exception->errors());
    }
}
