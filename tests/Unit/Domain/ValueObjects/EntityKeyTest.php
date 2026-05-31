<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EntityKeyTest extends TestCase
{
    public function test_constructor_stores_value_and_default_type(): void
    {
        $key = new EntityKey(42);

        self::assertSame(42, $key->value);
        self::assertSame('int', $key->type);
    }

    public function test_constructor_accepts_custom_type(): void
    {
        $key = new EntityKey('abc-123', 'string');

        self::assertSame('abc-123', $key->value);
        self::assertSame('string', $key->type);
    }

    public function test_equals_returns_true_when_value_and_type_match(): void
    {
        $a = new EntityKey(1, 'int');
        $b = new EntityKey(1, 'int');

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_when_values_differ(): void
    {
        $a = new EntityKey(1, 'int');
        $b = new EntityKey(2, 'int');

        self::assertFalse($a->equals($b));
    }

    public function test_equals_returns_false_when_types_differ(): void
    {
        $a = new EntityKey(1, 'int');
        $b = new EntityKey(1, 'string');

        self::assertFalse($a->equals($b));
    }

    public function test_to_string_returns_string_representation_of_value(): void
    {
        $key = new EntityKey(99, 'int');

        self::assertSame('99', (string) $key);
    }

    public function test_to_string_with_string_value(): void
    {
        $key = new EntityKey('some-uuid', 'string');

        self::assertSame('some-uuid', (string) $key);
    }

    // -------------------------------------------------------------------------
    // Security: type validation
    // -------------------------------------------------------------------------

    public function test_rejects_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("EntityKey type must be 'int' or 'string'");

        new EntityKey(1, 'array');
    }

    public function test_rejects_empty_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EntityKey(1, '');
    }

    public function test_accepts_int_value(): void
    {
        $key = new EntityKey(42);

        self::assertSame(42, $key->value);
    }

    public function test_accepts_string_value(): void
    {
        $key = new EntityKey('uuid-here', 'string');

        self::assertSame('uuid-here', $key->value);
    }

    public function test_accepts_zero_as_valid_int(): void
    {
        $key = new EntityKey(0);

        self::assertSame(0, $key->value);
    }

    public function test_accepts_empty_string_as_valid_string(): void
    {
        $key = new EntityKey('', 'string');

        self::assertSame('', $key->value);
    }

    public function test_accepts_negative_int(): void
    {
        $key = new EntityKey(-1);

        self::assertSame(-1, $key->value);
    }
}
