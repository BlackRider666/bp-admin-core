<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;
use PHPUnit\Framework\TestCase;

final class FieldNameTest extends TestCase
{
    public function test_constructor_stores_value(): void
    {
        $fieldName = new FieldName('first_name');

        self::assertSame('first_name', $fieldName->value);
    }

    public function test_equals_returns_true_when_values_match(): void
    {
        $a = new FieldName('email');
        $b = new FieldName('email');

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_when_values_differ(): void
    {
        $a = new FieldName('email');
        $b = new FieldName('phone');

        self::assertFalse($a->equals($b));
    }

    public function test_to_string_returns_value(): void
    {
        $fieldName = new FieldName('last_name');

        self::assertSame('last_name', (string) $fieldName);
    }
}
