<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;

/**
 * Tests for FieldContract::expandRules(array $locales, string $context): array
 * default implementation on AbstractField (exercised through TextField).
 *
 * Acceptance: A4, A6 — AbstractField default returns a single-key array keyed
 * by the field name, ignoring locales (non-translatable fields do not expand).
 *
 * Pre-IMPL status: expandRules() method does not exist → Error → RED.
 */
final class FieldExpandRulesTest extends TestCase
{
    public function test_expand_rules_returns_single_key_equal_to_field_name(): void
    {
        $field = TextField::make('email')->required();

        $result = $field->expandRules([], 'create');

        self::assertArrayHasKey('email', $result);
    }

    public function test_expand_rules_value_equals_ruleSet_toArray(): void
    {
        $field = TextField::make('slug')->withRules(['required', 'max:255']);

        $result = $field->expandRules([], 'create');

        self::assertSame(['slug' => ['required', 'max:255']], $result);
    }

    public function test_expand_rules_with_locales_does_not_expand_plain_field(): void
    {
        // AbstractField is NOT locale-aware — ignores $locales entirely.
        $field = TextField::make('title')->required();

        $result = $field->expandRules(['en', 'uk'], 'create');

        self::assertArrayHasKey('title', $result);
        self::assertArrayNotHasKey('title.en', $result);
        self::assertArrayNotHasKey('title.uk', $result);
    }

    public function test_expand_rules_returns_exactly_one_key_regardless_of_locales(): void
    {
        $field = TextField::make('status')->required();

        $result = $field->expandRules(['en', 'fr', 'de'], 'create');

        self::assertCount(1, $result);
    }

    public function test_expand_rules_in_update_context_returns_raw_rules_without_relaxation(): void
    {
        // AbstractField.expandRules does NOT apply presentKeys relaxation —
        // that is exclusively RuleBuilder's responsibility.
        $field = TextField::make('name')->required();

        $result = $field->expandRules([], 'update');

        self::assertArrayHasKey('name', $result);
        self::assertContains('required', $result['name']);
    }

    public function test_expand_rules_with_empty_rules_returns_single_entry_with_empty_array(): void
    {
        $field = TextField::make('bio'); // no rules

        $result = $field->expandRules([], 'create');

        self::assertSame(['bio' => []], $result);
    }

    public function test_expand_rules_preserves_parameterized_rules(): void
    {
        $field = TextField::make('username')->withRules(['required', 'min:3', 'max:30']);

        $result = $field->expandRules([], 'create');

        self::assertContains('min:3', $result['username']);
        self::assertContains('max:30', $result['username']);
    }
}
