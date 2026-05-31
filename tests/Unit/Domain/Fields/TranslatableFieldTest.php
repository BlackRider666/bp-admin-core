<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use PHPUnit\Framework\TestCase;

final class TranslatableFieldTest extends TestCase
{
    public function test_translatable_field_type_returns_translatable(): void
    {
        self::assertSame('translatable', TranslatableField::make('title')->type());
    }

    public function test_translatable_field_make_creates_instance(): void
    {
        $field = TranslatableField::make('name');

        self::assertInstanceOf(TranslatableField::class, $field);
        self::assertSame('name', $field->name());
    }

    public function test_translatable_field_inner_type_defaults_to_text(): void
    {
        $field = TranslatableField::make('title');

        self::assertSame('text', $field->innerType());
    }

    public function test_translatable_field_as_editor_changes_inner_type(): void
    {
        $field = TranslatableField::make('content')->asEditor();

        self::assertSame('editor', $field->innerType());
    }

    public function test_translatable_field_as_editor_returns_same_instance(): void
    {
        $field = TranslatableField::make('body');
        $result = $field->asEditor();

        self::assertSame($field, $result);
    }

    public function test_translatable_field_fluent_api_works(): void
    {
        $field = TranslatableField::make('description');

        $result = $field
            ->withLabel('Description')
            ->required()
            ->sortable()
            ->filterable()
            ->withMeta(['locales' => ['en', 'uk']])
            ->hideFromList()
            ->hideFromShow();

        self::assertSame($field, $result);
        self::assertSame('Description', $field->label());
        self::assertContains('required', $field->rules());
        self::assertTrue($field->isSortable());
        self::assertTrue($field->isFilterable());
        self::assertSame(['locales' => ['en', 'uk']], $field->meta());
        self::assertFalse($field->visibleOnList());
        self::assertTrue($field->visibleOnForm());
        self::assertFalse($field->visibleOnShow());
    }
}
