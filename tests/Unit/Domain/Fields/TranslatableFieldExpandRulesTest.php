<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TranslatableField::expandRules(array $locales, string $context): array
 *
 * Acceptance A4: when locales are provided, TranslatableField emits one
 * dot-notation key per locale (`field.locale`), each carrying the field's rules.
 * When locales is empty, falls back to the flat field-name key (legacy path).
 *
 * Pre-IMPL status: expandRules() method does not exist → Error → RED.
 */
final class TranslatableFieldExpandRulesTest extends TestCase
{
    public function test_expand_rules_with_locales_returns_dot_key_per_locale(): void
    {
        $field = TranslatableField::make('title')->required();

        $result = $field->expandRules(['en', 'uk'], 'create');

        self::assertArrayHasKey('title.en', $result);
        self::assertArrayHasKey('title.uk', $result);
    }

    public function test_expand_rules_with_locales_contains_required_rule_on_each_key(): void
    {
        $field = TranslatableField::make('title')->required();

        $result = $field->expandRules(['en', 'uk'], 'create');

        self::assertContains('required', $result['title.en']);
        self::assertContains('required', $result['title.uk']);
    }

    public function test_expand_rules_with_locales_excludes_flat_field_key(): void
    {
        $field = TranslatableField::make('title')->required();

        $result = $field->expandRules(['en', 'uk'], 'create');

        self::assertArrayNotHasKey('title', $result);
    }

    public function test_expand_rules_with_empty_locales_falls_back_to_flat_key(): void
    {
        // Regression guard: empty locales → no per-locale expansion.
        $field = TranslatableField::make('title')->required();

        $result = $field->expandRules([], 'create');

        self::assertArrayHasKey('title', $result);
        self::assertContains('required', $result['title']);
        self::assertArrayNotHasKey('title.en', $result);
    }

    public function test_expand_rules_with_three_locales_returns_three_keys(): void
    {
        $field = TranslatableField::make('body')->required();

        $result = $field->expandRules(['en', 'uk', 'de'], 'create');

        self::assertCount(3, $result);
        self::assertArrayHasKey('body.de', $result);
    }

    public function test_expand_rules_propagates_same_rules_to_each_locale_key(): void
    {
        $field = TranslatableField::make('excerpt')->withRules(['required', 'max:500']);

        $result = $field->expandRules(['en', 'uk'], 'create');

        self::assertContains('max:500', $result['excerpt.en']);
        self::assertContains('max:500', $result['excerpt.uk']);
    }

    public function test_expand_rules_with_locales_and_no_rules_has_no_flat_key(): void
    {
        // When there are no rules, expandRules must not return a flat field key —
        // the shape is still locale-keyed (entries may be empty arrays).
        $field = TranslatableField::make('bio'); // no rules

        $result = $field->expandRules(['en', 'uk'], 'create');

        // No flat key — only locale-keyed entries (possibly with empty arrays)
        // or an empty result. RuleBuilder will filter out empty rule arrays.
        self::assertArrayNotHasKey('bio', $result);
    }

    public function test_expand_rules_in_update_context_still_produces_dot_keys(): void
    {
        // Context is available in expandRules signature; TranslatableField produces
        // the same locale-keyed shape in both contexts — RuleBuilder handles relaxation.
        $field = TranslatableField::make('title')->required();

        $result = $field->expandRules(['en'], 'update');

        self::assertArrayHasKey('title.en', $result);
        self::assertArrayNotHasKey('title', $result);
    }
}
