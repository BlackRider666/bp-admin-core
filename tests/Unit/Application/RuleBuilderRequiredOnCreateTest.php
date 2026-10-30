<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\RuleBuilder;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\FileField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;

/** A11 / A52 — requiredOnCreate() and `{field}__clear` rules. */
final class RuleBuilderRequiredOnCreateTest extends TestCase
{
    /** @param array<mixed> $fields */
    private function def(array $fields): EntityDefinitionContract
    {
        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('fields')->willReturn($fields);
        return $def;
    }

    public function test_field_flag_is_off_by_default_and_on_after_required_on_create(): void
    {
        $plain = FileField::make('doc');
        $flagged = FileField::make('doc')->requiredOnCreate();

        self::assertFalse($plain->isRequiredOnCreate());
        self::assertTrue($flagged->isRequiredOnCreate());
    }

    public function test_required_on_create_is_fluent_on_text_field_too(): void
    {
        $f = TextField::make('title');

        self::assertSame($f, $f->requiredOnCreate());
    }

    public function test_create_context_emits_required_for_required_on_create_field(): void
    {
        $def = $this->def([FileField::make('doc')->requiredOnCreate()]);

        $rules = (new RuleBuilder([], 'create'))->build($def);

        self::assertContains('required', $rules['doc']);
    }

    public function test_update_context_emits_nullable_not_required_when_key_present(): void
    {
        $def = $this->def([FileField::make('doc')->requiredOnCreate()]);

        $rules = (new RuleBuilder([], 'update'))->build($def, ['doc']);

        self::assertContains('nullable', $rules['doc']);
        self::assertNotContains('required', $rules['doc']);
    }

    public function test_update_context_emits_nullable_not_required_when_key_absent(): void
    {
        $def = $this->def([FileField::make('doc')->requiredOnCreate()]);

        $rules = (new RuleBuilder([], 'update'))->build($def, []);

        self::assertContains('nullable', $rules['doc']);
        self::assertNotContains('required', $rules['doc']);
    }

    public function test_update_clear_on_required_on_create_file_field_is_prohibited(): void
    {
        $def = $this->def([FileField::make('doc')->requiredOnCreate()]);

        $rules = (new RuleBuilder([], 'update'))->build($def, ['doc__clear']);

        self::assertArrayHasKey('doc__clear', $rules);
        self::assertContains('prohibited', $rules['doc__clear']);
    }

    public function test_update_clear_on_required_file_field_is_prohibited(): void
    {
        $def = $this->def([FileField::make('doc')->required()]);

        $rules = (new RuleBuilder([], 'update'))->build($def, ['doc__clear']);

        self::assertArrayHasKey('doc__clear', $rules);
        self::assertContains('prohibited', $rules['doc__clear']);
    }

    public function test_update_clear_on_optional_file_field_allows_boolean(): void
    {
        $def = $this->def([FileField::make('doc')]);

        $rules = (new RuleBuilder([], 'update'))->build($def, ['doc__clear']);

        self::assertArrayHasKey('doc__clear', $rules);
        self::assertContains('boolean', $rules['doc__clear']);
        self::assertNotContains('prohibited', $rules['doc__clear']);
    }

    public function test_update_clear_rule_is_not_emitted_for_non_file_field(): void
    {
        $def = $this->def([TextField::make('title')]);

        $rules = (new RuleBuilder([], 'update'))->build($def, ['title']);

        self::assertArrayNotHasKey('title__clear', $rules);
    }
}
