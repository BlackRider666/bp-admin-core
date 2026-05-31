<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\ImageField;
use PHPUnit\Framework\TestCase;

final class ImageFieldTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Default validation rules
    // -------------------------------------------------------------------------

    public function test_image_field_has_nullable_rule_by_default(): void
    {
        $field = ImageField::make('avatar');

        self::assertContains('nullable', $field->rules());
    }

    public function test_image_field_has_file_rule_by_default(): void
    {
        $field = ImageField::make('avatar');

        self::assertContains('file', $field->rules());
    }

    public function test_image_field_has_image_rule_by_default(): void
    {
        $field = ImageField::make('avatar');

        self::assertContains('image', $field->rules());
    }

    // -------------------------------------------------------------------------
    // directory() fluent method
    // -------------------------------------------------------------------------

    public function test_image_field_directory_fluent_api(): void
    {
        $field = ImageField::make('avatar');
        $result = $field->directory('uploads/images');

        self::assertSame($field, $result);
        self::assertSame('uploads/images', $field->getDirectory());
    }

    // -------------------------------------------------------------------------
    // disk() fluent method
    // -------------------------------------------------------------------------

    public function test_image_field_disk_fluent_api(): void
    {
        $field = ImageField::make('avatar');
        $result = $field->disk('public');

        self::assertSame($field, $result);
        self::assertSame('public', $field->getDisk());
    }

    // -------------------------------------------------------------------------
    // maxWidth() fluent method
    // -------------------------------------------------------------------------

    public function test_image_field_max_width_defaults_to_zero(): void
    {
        $field = ImageField::make('avatar');

        self::assertSame(0, $field->getMaxWidth());
    }

    public function test_image_field_max_width_fluent_api(): void
    {
        $field = ImageField::make('avatar');
        $result = $field->maxWidth(1920);

        self::assertSame($field, $result);
        self::assertSame(1920, $field->getMaxWidth());
    }
}
