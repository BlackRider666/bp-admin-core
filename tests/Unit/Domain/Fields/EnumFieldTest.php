<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\EnumField;
use PHPUnit\Framework\TestCase;

final class EnumFieldTest extends TestCase
{
    public function test_make_creates_instance_with_name_and_options(): void
    {
        $field = EnumField::make('status', ['active', 'inactive', 'pending']);

        self::assertInstanceOf(EnumField::class, $field);
        self::assertSame('status', $field->name());
    }

    public function test_type_returns_enum(): void
    {
        $field = EnumField::make('status', []);

        self::assertSame('enum', $field->type());
    }

    public function test_options_returns_provided_array(): void
    {
        $options = ['active', 'inactive', 'pending'];
        $field = EnumField::make('status', $options);

        self::assertSame($options, $field->options());
    }

    public function test_make_with_empty_options_returns_empty_array(): void
    {
        $field = EnumField::make('status');

        self::assertSame([], $field->options());
    }

    public function test_meta_includes_options_key(): void
    {
        $options = ['draft', 'published'];
        $field = EnumField::make('status', $options);

        $meta = $field->meta();

        self::assertArrayHasKey('options', $meta);
        self::assertSame($options, $meta['options']);
    }

    public function test_meta_merges_parent_meta_with_options(): void
    {
        $options = ['yes', 'no'];
        $field = EnumField::make('answer', $options)
            ->withMeta(['tooltip' => 'Select an answer']);

        $meta = $field->meta();

        self::assertSame('Select an answer', $meta['tooltip']);
        self::assertSame($options, $meta['options']);
    }

    public function test_label_auto_generated_from_name(): void
    {
        $field = EnumField::make('order_status', ['pending', 'shipped']);

        self::assertSame('Order status', $field->label());
    }

    public function test_with_label_overrides_auto_generated_label(): void
    {
        $field = EnumField::make('status', [])->withLabel('Current Status');

        self::assertSame('Current Status', $field->label());
    }

    public function test_fluent_api_works_with_enum_field(): void
    {
        $field = EnumField::make('role', ['admin', 'user'])
            ->withLabel('User Role')
            ->required()
            ->sortable();

        self::assertSame('User Role', $field->label());
        self::assertContains('required', $field->rules());
        self::assertTrue($field->isSortable());
        self::assertSame(['admin', 'user'], $field->options());
    }
}
