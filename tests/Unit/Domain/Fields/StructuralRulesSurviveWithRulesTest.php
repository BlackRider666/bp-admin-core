<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\EnumField;
use BlackParadise\CoreAdmin\Domain\Fields\FileField;
use BlackParadise\CoreAdmin\Domain\Fields\ImageField;
use PHPUnit\Framework\TestCase;

/**
 * A6 (B5): After ->withRules([...]), structural rules (file/image/nullable/array)
 * must still be present in the field's rule set.
 *
 * Currently BUGS: withRules() replaces $ruleSetInstance entirely, wiping
 * the structural rules that were added in the constructor.
 * These tests will be RED against the current code.
 */
final class StructuralRulesSurviveWithRulesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // FileField — structural rules survive withRules()
    // -------------------------------------------------------------------------

    public function test_file_field_still_has_file_rule_after_with_rules(): void
    {
        $field = FileField::make('document')->withRules(['max:1024']);

        self::assertContains(
            'file',
            $field->rules(),
            'FileField must still enforce "file" rule after withRules() call.',
        );
    }

    public function test_file_field_still_has_nullable_rule_after_with_rules(): void
    {
        $field = FileField::make('document')->withRules(['max:1024']);

        self::assertContains(
            'nullable',
            $field->rules(),
            'FileField must still enforce "nullable" rule after withRules() call.',
        );
    }

    public function test_file_field_user_rules_coexist_with_structural_rules(): void
    {
        $field = FileField::make('document')->withRules(['max:1024']);

        self::assertContains('max:1024', $field->rules());
        self::assertContains('file', $field->rules());
        self::assertContains('nullable', $field->rules());
    }

    // -------------------------------------------------------------------------
    // ImageField — structural rules survive withRules()
    // -------------------------------------------------------------------------

    public function test_image_field_still_has_image_rule_after_with_rules(): void
    {
        $field = ImageField::make('avatar')->withRules(['max:2048']);

        self::assertContains(
            'image',
            $field->rules(),
            'ImageField must still enforce "image" rule after withRules() call.',
        );
    }

    public function test_image_field_still_has_nullable_rule_after_with_rules(): void
    {
        $field = ImageField::make('avatar')->withRules(['max:2048']);

        self::assertContains(
            'nullable',
            $field->rules(),
            'ImageField must still enforce "nullable" rule after withRules() call.',
        );
    }

    public function test_image_field_user_rules_coexist_with_structural_rules(): void
    {
        $field = ImageField::make('avatar')->withRules(['max:2048']);

        self::assertContains('max:2048', $field->rules());
        self::assertContains('image', $field->rules());
        self::assertContains('nullable', $field->rules());
    }

    // -------------------------------------------------------------------------
    // EnumField::multiple() — 'array' structural rule survives withRules()
    // -------------------------------------------------------------------------

    public function test_enum_field_multiple_still_has_array_rule_after_with_rules(): void
    {
        $field = EnumField::make('tags', ['a' => 'A', 'b' => 'B'])
            ->multiple()
            ->withRules(['max:5']);

        self::assertContains(
            'array',
            $field->rules(),
            'EnumField->multiple()->withRules() must still have "array" structural rule.',
        );
    }

    public function test_enum_field_multiple_user_rules_coexist_with_array_rule(): void
    {
        $field = EnumField::make('tags', ['a' => 'A', 'b' => 'B'])
            ->multiple()
            ->withRules(['max:5']);

        self::assertContains('max:5', $field->rules());
        self::assertContains('array', $field->rules());
    }

    public function test_enum_field_without_multiple_has_no_array_rule_after_with_rules(): void
    {
        // Without multiple(), 'array' should NOT be added.
        $field = EnumField::make('status', ['a' => 'A'])->withRules(['required']);

        self::assertNotContains('array', $field->rules());
        self::assertContains('required', $field->rules());
    }

    // -------------------------------------------------------------------------
    // A6 regression: ->required() must suppress the auto "nullable" structural
    // rule (a required file is NOT optional). Moving "nullable" into typeRules()
    // must not make it unreachable by required().
    // -------------------------------------------------------------------------

    public function test_file_field_required_suppresses_auto_nullable(): void
    {
        $rules = FileField::make('document')->required()->ruleSet()->toArray();

        self::assertContains('required', $rules);
        self::assertNotContains(
            'nullable',
            $rules,
            'A required FileField must not also carry the auto "nullable" rule.',
        );
        self::assertContains('file', $rules);
    }

    public function test_image_field_required_suppresses_auto_nullable(): void
    {
        $rules = ImageField::make('avatar')->required()->ruleSet()->toArray();

        self::assertContains('required', $rules);
        self::assertNotContains(
            'nullable',
            $rules,
            'A required ImageField must not also carry the auto "nullable" rule.',
        );
        self::assertContains('image', $rules);
    }
}
