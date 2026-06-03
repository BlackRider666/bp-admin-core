<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\RuleBuilder;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use PHPUnit\Framework\TestCase;

final class RuleBuilderTest extends TestCase
{
    public function test_from_definition_returns_rules_keyed_by_field_name(): void
    {
        $field = TextField::make('email')->withRules(['required', 'email']);

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame(['email' => ['required', 'email']], $rules);
    }

    public function test_from_definition_skips_fields_with_empty_rules(): void
    {
        $fieldWithRules = TextField::make('email')->withRules(['required', 'email']);
        $fieldWithoutRules = TextField::make('notes');

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$fieldWithRules, $fieldWithoutRules]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertArrayHasKey('email', $rules);
        self::assertArrayNotHasKey('notes', $rules);
    }

    public function test_from_definition_returns_empty_array_when_no_fields(): void
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame([], $rules);
    }

    public function test_from_definition_returns_empty_array_when_all_fields_have_empty_rules(): void
    {
        $field1 = TextField::make('name');
        $field2 = TextField::make('bio');

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field1, $field2]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame([], $rules);
    }

    public function test_from_definition_returns_rules_for_multiple_fields(): void
    {
        $nameField = TextField::make('name')->withRules(['required', 'max:100']);
        $emailField = TextField::make('email')->withRules(['required', 'email']);
        $notesField = TextField::make('notes'); // no rules — should be skipped

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$nameField, $emailField, $notesField]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame([
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email'],
        ], $rules);
    }

    // -------------------------------------------------------------------------
    // Bug #5 — TranslatableField locale expansion
    // -------------------------------------------------------------------------

    public function test_build_with_locales_expands_translatable_field_to_per_locale_keys(): void
    {
        $field = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = (new RuleBuilder(['en', 'uk']))->build($definition);

        self::assertArrayHasKey('title.en', $rules);
        self::assertArrayHasKey('title.uk', $rules);
        self::assertContains('required', $rules['title.en']);
        self::assertContains('required', $rules['title.uk']);
        // The flat top-level key must NOT exist.
        self::assertArrayNotHasKey('title', $rules);
    }

    public function test_build_with_empty_locales_uses_flat_key_for_translatable_field(): void
    {
        // No locales supplied → legacy flat-key path.
        $field = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = (new RuleBuilder())->build($definition);

        self::assertArrayHasKey('title', $rules);
        self::assertContains('required', $rules['title']);
    }

    public function test_build_with_locales_skips_translatable_field_when_no_rules(): void
    {
        $field = TranslatableField::make('bio'); // no rules

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = (new RuleBuilder(['en', 'uk']))->build($definition);

        // No keys at all — nothing to validate.
        self::assertSame([], $rules);
    }

    public function test_build_with_locales_does_not_affect_non_translatable_fields(): void
    {
        $textField = TextField::make('slug')->withRules(['required']);
        $translatableField = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$textField, $translatableField]);

        $rules = (new RuleBuilder(['en', 'uk']))->build($definition);

        // Non-translatable field uses its plain name.
        self::assertArrayHasKey('slug', $rules);
        self::assertContains('required', $rules['slug']);

        // Translatable field is expanded.
        self::assertArrayHasKey('title.en', $rules);
        self::assertArrayHasKey('title.uk', $rules);
        self::assertArrayNotHasKey('title', $rules);
    }

    public function test_from_definition_static_fallback_still_uses_flat_key_for_translatable(): void
    {
        // fromDefinition() is the legacy static method — it must NOT expand locales
        // so existing callers that have not been migrated to the locale-aware path
        // continue to work unchanged.
        $field = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertArrayHasKey('title', $rules);
        self::assertArrayNotHasKey('title.en', $rules);
    }

    // -------------------------------------------------------------------------
    // Minor — RuleBuilder locale sanitisation (empty/non-string values)
    // -------------------------------------------------------------------------

    public function test_builder_filters_empty_string_locales(): void
    {
        // An empty string locale would produce a malformed key like "title."
        // which Laravel's validator would misinterpret. The constructor must
        // silently drop such entries.
        $field = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = (new RuleBuilder(['en', '', 'uk']))->build($definition);

        // Only valid locales should produce keys.
        self::assertArrayHasKey('title.en', $rules);
        self::assertArrayHasKey('title.uk', $rules);
        // The empty-string locale must NOT produce a key.
        self::assertArrayNotHasKey('title.', $rules);
    }

    public function test_builder_with_only_empty_string_locales_falls_back_to_flat_key(): void
    {
        // After filtering, if no valid locales remain, the builder must behave
        // as if no locales were supplied — i.e. fall back to flat key.
        $field = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = (new RuleBuilder(['', '']))->build($definition);

        // No valid locales → flat key fallback (legacy path).
        self::assertArrayHasKey('title', $rules);
        self::assertArrayNotHasKey('title.', $rules);
    }

    public function test_builder_filters_non_string_locale_values(): void
    {
        // Non-string values (int, null, bool) must be silently dropped so that
        // misconfigured adapter code does not produce unexpected rule keys.
        $field = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        // Pass a mixed array — only 'en' and 'uk' are valid strings.
        $rules = (new RuleBuilder(['en', 42, null, true, 'uk']))->build($definition);

        self::assertArrayHasKey('title.en', $rules);
        self::assertArrayHasKey('title.uk', $rules);
        // Non-string entries must not produce any keys.
        self::assertCount(2, $rules); // only title.en and title.uk
    }
}
