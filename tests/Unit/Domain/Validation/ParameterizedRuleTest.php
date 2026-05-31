<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Validation;

use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ParameterizedRuleTest extends TestCase
{
    public function test_stores_name_and_value(): void
    {
        $rule = new ParameterizedRule('max', 255);

        self::assertSame('max', $rule->name);
        self::assertSame(255, $rule->value);
    }

    public function test_accepts_string_value(): void
    {
        $rule = new ParameterizedRule('in', 'a,b,c');

        self::assertSame('in', $rule->name);
        self::assertSame('a,b,c', $rule->value);
    }

    public function test_accepts_array_value(): void
    {
        $rule = new ParameterizedRule('in', ['a', 'b', 'c']);

        self::assertSame(['a', 'b', 'c'], $rule->value);
    }

    public function test_accepts_null_value(): void
    {
        $rule = new ParameterizedRule('unique', null);

        self::assertSame('unique', $rule->name);
        self::assertNull($rule->value);
    }

    public function test_is_readonly(): void
    {
        $rule = new ParameterizedRule('max', 255);

        $ref = new ReflectionClass($rule);
        self::assertTrue($ref->isReadOnly());
    }
}
