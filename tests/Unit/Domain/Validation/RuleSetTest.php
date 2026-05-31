<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Validation;

use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;
use BlackParadise\CoreAdmin\Domain\Validation\RuleSet;
use PHPUnit\Framework\TestCase;

final class RuleSetTest extends TestCase
{
    public function test_empty_by_default(): void
    {
        $set = new RuleSet();

        self::assertTrue($set->isEmpty());
        self::assertSame([], $set->all());
        self::assertSame([], $set->toArray());
    }

    public function test_add_simple_rule(): void
    {
        $set = new RuleSet();
        $set->add(Rule::Required);

        self::assertFalse($set->isEmpty());
        self::assertTrue($set->has(Rule::Required));
        self::assertSame(['required'], $set->toArray());
    }

    public function test_add_does_not_duplicate_simple_rules(): void
    {
        $set = new RuleSet();
        $set->add(Rule::Required);
        $set->add(Rule::Required);

        self::assertCount(1, $set->all());
        self::assertSame(['required'], $set->toArray());
    }

    public function test_add_parameterized_rule(): void
    {
        $set = new RuleSet();
        $set->add(new ParameterizedRule('max', 255));

        self::assertSame(['max:255'], $set->toArray());
    }

    public function test_add_parameterized_rule_with_array_value(): void
    {
        $set = new RuleSet();
        $set->add(new ParameterizedRule('in', ['a', 'b', 'c']));

        self::assertSame(['in:a,b,c'], $set->toArray());
    }

    public function test_remove_simple_rule(): void
    {
        $set = new RuleSet();
        $set->add(Rule::Required);
        $set->add(Rule::String);
        $set->remove(Rule::Required);

        self::assertFalse($set->has(Rule::Required));
        self::assertTrue($set->has(Rule::String));
    }

    public function test_remove_nonexistent_rule_is_safe(): void
    {
        $set = new RuleSet();
        $set->remove(Rule::Required);

        self::assertTrue($set->isEmpty());
    }

    public function test_has_returns_false_for_missing_rule(): void
    {
        $set = new RuleSet();

        self::assertFalse($set->has(Rule::Required));
    }

    public function test_constructor_accepts_initial_rules(): void
    {
        $set = new RuleSet([Rule::Required, new ParameterizedRule('max', 255)]);

        self::assertSame(['required', 'max:255'], $set->toArray());
    }

    public function test_mixed_rules_to_array(): void
    {
        $set = new RuleSet();
        $set->add(Rule::Required);
        $set->add(Rule::String);
        $set->add(new ParameterizedRule('max', 255));
        $set->add(new ParameterizedRule('min', 1));

        self::assertSame(['required', 'string', 'max:255', 'min:1'], $set->toArray());
    }

    public function test_add_returns_self_for_chaining(): void
    {
        $set = new RuleSet();
        $result = $set->add(Rule::Required);

        self::assertSame($set, $result);
    }

    public function test_remove_returns_self_for_chaining(): void
    {
        $set = new RuleSet();
        $result = $set->remove(Rule::Required);

        self::assertSame($set, $result);
    }

    public function test_required_then_nullable_replaces_via_field(): void
    {
        // Simulates what AbstractField::nullable() does
        $set = new RuleSet();
        $set->add(Rule::Required);
        $set->remove(Rule::Required)->add(Rule::Nullable);

        self::assertFalse($set->has(Rule::Required));
        self::assertTrue($set->has(Rule::Nullable));
        self::assertSame(['nullable'], $set->toArray());
    }

    public function test_parameterized_rule_with_null_value(): void
    {
        $set = new RuleSet();
        $set->add(new ParameterizedRule('unique', null));

        self::assertSame(['unique:'], $set->toArray());
    }
}
