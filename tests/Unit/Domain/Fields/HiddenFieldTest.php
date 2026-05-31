<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\HiddenField;
use PHPUnit\Framework\TestCase;

final class HiddenFieldTest extends TestCase
{
    public function test_hidden_field_type_returns_hidden(): void
    {
        self::assertSame('hidden', HiddenField::make('token')->type());
    }

    public function test_hidden_field_make_creates_instance(): void
    {
        $field = HiddenField::make('csrf_token');

        self::assertInstanceOf(HiddenField::class, $field);
        self::assertSame('csrf_token', $field->name());
    }

    public function test_hidden_field_is_not_visible_on_list_by_default(): void
    {
        $field = HiddenField::make('token');

        self::assertFalse($field->visibleOnList());
    }

    public function test_hidden_field_is_visible_on_form_by_default(): void
    {
        $field = HiddenField::make('token');

        self::assertTrue($field->visibleOnForm());
    }

    public function test_hidden_field_is_not_visible_on_show_by_default(): void
    {
        $field = HiddenField::make('token');

        self::assertFalse($field->visibleOnShow());
    }

    public function test_hidden_field_fluent_api_works(): void
    {
        $field = HiddenField::make('internal_id');

        $result = $field
            ->withLabel('Internal ID')
            ->required()
            ->sortable()
            ->withMeta(['hint' => 'auto-generated']);

        self::assertSame($field, $result);
        self::assertSame('Internal ID', $field->label());
        self::assertContains('required', $field->rules());
        self::assertTrue($field->isSortable());
        self::assertSame(['hint' => 'auto-generated'], $field->meta());
    }
}
