<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Validation;

use BlackParadise\CoreAdmin\Domain\Validation\Rule;
use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase
{
    public function test_required_has_correct_value(): void
    {
        self::assertSame('required', Rule::Required->value);
    }

    public function test_nullable_has_correct_value(): void
    {
        self::assertSame('nullable', Rule::Nullable->value);
    }

    public function test_string_has_correct_value(): void
    {
        self::assertSame('string', Rule::String->value);
    }

    public function test_integer_has_correct_value(): void
    {
        self::assertSame('integer', Rule::Integer->value);
    }

    public function test_email_has_correct_value(): void
    {
        self::assertSame('email', Rule::Email->value);
    }

    public function test_boolean_has_correct_value(): void
    {
        self::assertSame('boolean', Rule::Boolean->value);
    }

    public function test_try_from_returns_enum_for_valid_value(): void
    {
        self::assertSame(Rule::Required, Rule::tryFrom('required'));
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        self::assertNull(Rule::tryFrom('max'));
    }

    public function test_all_cases_are_backed_by_string(): void
    {
        foreach (Rule::cases() as $case) {
            self::assertIsString($case->value);
            self::assertNotEmpty($case->value);
        }
    }
}
