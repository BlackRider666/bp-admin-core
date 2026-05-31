<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Query;

use BlackParadise\CoreAdmin\Domain\Query\Filter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FilterTest extends TestCase
{
    public function test_constructor_stores_field_and_value_with_default_operator(): void
    {
        $filter = new Filter('status', 'active');

        self::assertSame('status', $filter->field);
        self::assertSame('active', $filter->value);
        self::assertSame('=', $filter->operator);
    }

    public function test_constructor_accepts_custom_operator(): void
    {
        $filter = new Filter('age', 18, '>=');

        self::assertSame('age', $filter->field);
        self::assertSame(18, $filter->value);
        self::assertSame('>=', $filter->operator);
    }

    public function test_constructor_accepts_null_value(): void
    {
        $filter = new Filter('deleted_at', null);

        self::assertNull($filter->value);
        self::assertSame('=', $filter->operator);
    }

    /**
     * @dataProvider allowedOperatorProvider
     */
    public function test_allowed_operators_are_accepted(string $operator): void
    {
        $filter = new Filter('name', 'x', $operator);

        self::assertSame($operator, $filter->operator);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function allowedOperatorProvider(): array
    {
        return [
            ['='],
            ['!='],
            ['<'],
            ['>'],
            ['<='],
            ['>='],
            ['like'],
            ['in'],
            ['not in'],
        ];
    }

    public function test_uppercase_operator_in_allowlist_is_accepted_case_insensitively(): void
    {
        // The allowlist match is lowercased — uppercase variants of allowed
        // operators (LIKE, IN) must be accepted.
        $filter = new Filter('name', 'x', 'LIKE');

        self::assertSame('LIKE', $filter->operator);
    }

    public function test_unknown_operator_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/operator must be one of/i');

        new Filter('deleted_at', null, 'IS NULL');
    }

    public function test_sql_injection_attempt_in_operator_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Filter('id', 1, '= 1; DROP TABLE users;--');
    }

    public function test_empty_operator_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Filter('id', 1, '');
    }
}
