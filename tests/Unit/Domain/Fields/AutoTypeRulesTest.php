<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\NumberField;
use BlackParadise\CoreAdmin\Domain\Fields\TextareaField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use PHPUnit\Framework\TestCase;

/**
 * Bug #6 — Auto type/length rules emitted by concrete field classes.
 *
 * Core-side fix: fields emit structural constraints based on their type and
 * explicit parameters (e.g. maxLength) via the protected typeRules() hook.
 * No DB-schema introspection — only field type + explicit fluent configuration.
 */
final class AutoTypeRulesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // NumberField — always emits numeric (or integer)
    // -------------------------------------------------------------------------

    public function test_number_field_emits_numeric_rule_by_default(): void
    {
        $field = NumberField::make('price');

        self::assertContains('numeric', $field->ruleSet()->toArray());
    }

    public function test_number_field_integer_mode_emits_integer_rule_not_numeric(): void
    {
        $field = NumberField::make('quantity')->integer();

        self::assertContains('integer', $field->ruleSet()->toArray());
        self::assertNotContains('numeric', $field->ruleSet()->toArray());
    }

    public function test_number_field_is_integer_returns_false_by_default(): void
    {
        self::assertFalse(NumberField::make('price')->isInteger());
    }

    public function test_number_field_is_integer_returns_true_after_integer_call(): void
    {
        self::assertTrue(NumberField::make('qty')->integer()->isInteger());
    }

    public function test_number_field_numeric_rule_combined_with_required(): void
    {
        $field = NumberField::make('price')->required();

        self::assertContains('required', $field->ruleSet()->toArray());
        self::assertContains('numeric', $field->ruleSet()->toArray());
    }

    public function test_number_field_integer_combined_with_required(): void
    {
        $field = NumberField::make('qty')->required()->integer();

        self::assertContains('required', $field->ruleSet()->toArray());
        self::assertContains('integer', $field->ruleSet()->toArray());
        self::assertNotContains('numeric', $field->ruleSet()->toArray());
    }

    public function test_number_field_numeric_survives_alongside_nullable(): void
    {
        // Auto-rules live in typeRules(), not in the explicit ruleSetInstance,
        // so they persist regardless of which explicit rules are active.
        $field = NumberField::make('price')->nullable();

        self::assertContains('nullable', $field->ruleSet()->toArray());
        self::assertContains('numeric', $field->ruleSet()->toArray());
        self::assertNotContains('required', $field->ruleSet()->toArray());
    }

    // -------------------------------------------------------------------------
    // TextField — emits max:<n> only when maxLength() is set
    // -------------------------------------------------------------------------

    public function test_text_field_no_max_rule_without_max_length(): void
    {
        $field = TextField::make('title');

        $maxRules = array_filter($field->ruleSet()->toArray(), static fn(string $r): bool => str_starts_with($r, 'max:'));
        self::assertEmpty($maxRules, 'TextField without maxLength must not emit any max:N rule');
    }

    public function test_text_field_emits_max_rule_when_max_length_set(): void
    {
        $field = TextField::make('title')->maxLength(255);

        self::assertContains('max:255', $field->ruleSet()->toArray());
    }

    public function test_text_field_max_length_combined_with_required(): void
    {
        $field = TextField::make('title')->required()->maxLength(100);

        self::assertContains('required', $field->ruleSet()->toArray());
        self::assertContains('max:100', $field->ruleSet()->toArray());
    }

    public function test_text_field_max_length_survives_alongside_nullable(): void
    {
        // maxLength is stored on the field instance and feeds typeRules(),
        // so it persists independently of which explicit rules are active.
        $field = TextField::make('title')->maxLength(255)->nullable();

        self::assertContains('nullable', $field->ruleSet()->toArray());
        self::assertContains('max:255', $field->ruleSet()->toArray());
        self::assertNotContains('required', $field->ruleSet()->toArray());
    }

    public function test_text_field_max_length_fluent_returns_same_instance(): void
    {
        $field = TextField::make('title');
        $result = $field->maxLength(100);

        self::assertSame($field, $result);
    }

    // -------------------------------------------------------------------------
    // TranslatableField — emits max:<n> only when maxLength() is set
    // -------------------------------------------------------------------------

    public function test_translatable_field_no_max_rule_without_max_length(): void
    {
        $field = TranslatableField::make('title');

        $maxRules = array_filter($field->ruleSet()->toArray(), static fn(string $r): bool => str_starts_with($r, 'max:'));
        self::assertEmpty($maxRules, 'TranslatableField without maxLength must not emit any max:N rule');
    }

    public function test_translatable_field_emits_max_rule_when_max_length_set(): void
    {
        $field = TranslatableField::make('title')->maxLength(500);

        self::assertContains('max:500', $field->ruleSet()->toArray());
    }

    public function test_translatable_field_max_length_combined_with_required(): void
    {
        $field = TranslatableField::make('body')->required()->maxLength(2000);

        self::assertContains('required', $field->ruleSet()->toArray());
        self::assertContains('max:2000', $field->ruleSet()->toArray());
    }

    public function test_translatable_field_max_length_survives_alongside_nullable(): void
    {
        // maxLength feeds typeRules() which is independent of the explicit rule set.
        $field = TranslatableField::make('title')->maxLength(255)->nullable();

        self::assertContains('nullable', $field->ruleSet()->toArray());
        self::assertContains('max:255', $field->ruleSet()->toArray());
        self::assertNotContains('required', $field->ruleSet()->toArray());
    }

    // -------------------------------------------------------------------------
    // maxLength on other field types — harmless (not consumed by typeRules())
    // -------------------------------------------------------------------------

    public function test_max_length_on_textarea_is_available_but_does_not_emit_auto_rule(): void
    {
        // TextareaField does not override typeRules(), so maxLength is stored
        // but does not produce a max rule (unless the developer adds it manually).
        $field = TextareaField::make('notes')->maxLength(1000);

        $maxRules = array_filter($field->ruleSet()->toArray(), static fn(string $r): bool => str_starts_with($r, 'max:'));
        self::assertEmpty($maxRules, 'TextareaField does not emit auto max rule — developer must add it manually');
    }

    // -------------------------------------------------------------------------
    // ruleSet() returns merged set (typeRules + explicit)
    // -------------------------------------------------------------------------

    public function test_rule_set_includes_auto_type_rules_from_number_field(): void
    {
        $field = NumberField::make('amount')->required();
        $ruleStrings = $field->ruleSet()->toArray();

        self::assertContains('required', $ruleStrings);
        self::assertContains('numeric', $ruleStrings);
    }

    public function test_rule_set_includes_max_from_text_field(): void
    {
        $field = TextField::make('slug')->required()->maxLength(100);
        $ruleStrings = $field->ruleSet()->toArray();

        self::assertContains('required', $ruleStrings);
        self::assertContains('max:100', $ruleStrings);
    }
}
