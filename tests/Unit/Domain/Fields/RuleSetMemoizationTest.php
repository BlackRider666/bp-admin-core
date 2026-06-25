<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\DateField;
use BlackParadise\CoreAdmin\Domain\Fields\EnumField;
use BlackParadise\CoreAdmin\Domain\Fields\NumberField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AbstractField::ruleSet() memoization (path-2: typeRules() !== []).
 *
 * RED driver: test_ruleset_returns_same_instance_on_repeated_calls
 *   → before memoization, path-2 clones the RuleSet every call → assertSame FAILS.
 *   → after memoization, same instance is returned → passes.
 *
 * Invalidation regression tests (GREEN both before AND after correct impl):
 *   They verify that calling a mutator causes ruleSet() to reflect the change.
 *   Before impl: no cache, so they trivially pass (fresh clone each time).
 *   After impl: cache is invalidated by each mutator, so they still pass.
 *   Declared via --pre-green in the gate invocation.
 */
final class RuleSetMemoizationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // RED driver — genuinely fails before memoization
    // -------------------------------------------------------------------------

    /**
     * ruleSet() must return the SAME instance on repeat calls when typeRules()
     * is non-empty (path-2). Before memoization, each call clones → different
     * objects → assertSame fails. After memoization → same object → passes.
     */
    public function test_ruleset_returns_same_instance_on_repeated_calls(): void
    {
        $field = TextField::make('title')->maxLength(255);

        $first  = $field->ruleSet();
        $second = $field->ruleSet();

        self::assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // Invalidation regression tests — GREEN before AND after impl
    // (declared --pre-green; see gate invocation)
    // -------------------------------------------------------------------------

    public function test_cache_invalidated_after_required(): void
    {
        $field = TextField::make('title')->maxLength(100);

        // Populate cache
        $before = $field->ruleSet()->toArray();

        // Mutate
        $field->required();

        // Cache must be invalidated → required rule now present
        self::assertContains('required', $field->ruleSet()->toArray());
        self::assertNotContains('required', $before);
    }

    public function test_cache_invalidated_after_nullable(): void
    {
        $field = TextField::make('title')->maxLength(100)->required();

        // Populate cache
        $before = $field->ruleSet()->toArray();

        $field->nullable();

        self::assertContains('nullable', $field->ruleSet()->toArray());
        self::assertNotContains('nullable', $before);
    }

    public function test_cache_invalidated_after_add_rule(): void
    {
        $field = TextField::make('title')->maxLength(100);

        // Populate cache
        $before = $field->ruleSet()->toArray();

        $field->addRule(Rule::Email);

        self::assertContains('email', $field->ruleSet()->toArray());
        self::assertNotContains('email', $before);
    }

    public function test_cache_invalidated_after_with_rules(): void
    {
        $field = TextField::make('title')->maxLength(100);

        // Populate cache — contains max:100, no required, no email
        $before = $field->ruleSet()->toArray();
        self::assertContains('max:100', $before);
        self::assertNotContains('required', $before);

        // withRules() replaces the entire ruleSetInstance (the most dangerous
        // invalidation site — if the call to invalidateRuleSetCache() is removed
        // from withRules(), this assertion will fail because the stale cache
        // would still return the old merged set without required/email).
        // @phpstan-ignore method.deprecated
        $field->withRules(['required', 'email']);

        $after = $field->ruleSet()->toArray();
        self::assertContains('required', $after);
        self::assertContains('email', $after);
        // max:100 was an auto type-rule appended from typeRules(), NOT part of
        // the old explicit ruleSetInstance — withRules() resets only the explicit
        // rules; typeRules() still emits max:100, so it appears in the new merged set.
        self::assertContains('max:100', $after);
        // The old explicit set had nothing; new explicit set has required+email.
        // Prove we are NOT returning the stale cache (which lacked required).
        self::assertNotContains('required', $before);
    }

    public function test_cache_invalidated_after_max_length(): void
    {
        $field = TextField::make('title')->maxLength(100);

        // Populate cache — contains max:100
        $before = $field->ruleSet()->toArray();

        // Change max length
        $field->maxLength(500);

        self::assertContains('max:500', $field->ruleSet()->toArray());
        self::assertNotContains('max:500', $before);
        self::assertContains('max:100', $before);
    }

    // -------------------------------------------------------------------------
    // Subclass mutator invalidation — NumberField (has post-construction mutators)
    // -------------------------------------------------------------------------

    public function test_number_field_cache_invalidated_after_integer(): void
    {
        $field = NumberField::make('age');

        // Populate cache (typeRules returns [Nullable, Numeric])
        $before = $field->ruleSet()->toArray();
        self::assertContains('numeric', $before);

        // Switch to integer mode
        $field->integer();

        self::assertContains('integer', $field->ruleSet()->toArray());
        self::assertNotContains('numeric', $field->ruleSet()->toArray());
    }

    public function test_number_field_cache_invalidated_after_min(): void
    {
        $field = NumberField::make('score');

        // Populate cache
        $before = $field->ruleSet()->toArray();
        self::assertNotContains('min:10', $before);

        $field->min(10);

        self::assertContains('min:10', $field->ruleSet()->toArray());
    }

    public function test_number_field_cache_invalidated_after_max(): void
    {
        $field = NumberField::make('score');

        // Populate cache
        $before = $field->ruleSet()->toArray();
        self::assertNotContains('max:100', $before);

        $field->max(100);

        self::assertContains('max:100', $field->ruleSet()->toArray());
    }

    // -------------------------------------------------------------------------
    // Subclass mutator invalidation — EnumField (multiple() is post-construction)
    // -------------------------------------------------------------------------

    public function test_enum_field_cache_invalidated_after_multiple(): void
    {
        $options = ['a' => 'A', 'b' => 'B'];
        $field   = EnumField::make('status', $options);

        // Populate cache — single mode emits [nullable, in:a,b]
        $before = $field->ruleSet()->toArray();
        self::assertContains('nullable', $before);

        // Switch to multiple mode
        $field->multiple();

        // multiple mode emits [array] instead
        self::assertContains('array', $field->ruleSet()->toArray());
        self::assertNotContains('nullable', $field->ruleSet()->toArray());
    }

    // -------------------------------------------------------------------------
    // Path-1 invariant: fields with empty typeRules() still return ruleSetInstance
    // -------------------------------------------------------------------------

    public function test_path1_field_with_no_type_rules_returns_ruleset_instance_directly(): void
    {
        // TextField with no maxLength → typeRules() returns [] → path-1
        $field = TextField::make('name');

        $first  = $field->ruleSet();
        $second = $field->ruleSet();

        // path-1 always returns $this->ruleSetInstance; assertSame must pass
        // both before AND after memoization impl (path-1 is not memoized)
        self::assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // DateField — constant typeRules, path-2 memoized
    // -------------------------------------------------------------------------

    public function test_date_field_ruleset_returns_same_instance_on_repeated_calls(): void
    {
        $field = DateField::make('created_at');

        $first  = $field->ruleSet();
        $second = $field->ruleSet();

        self::assertSame($first, $second);
    }
}
