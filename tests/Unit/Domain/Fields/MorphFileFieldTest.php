<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\MorphFileField;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MorphFileFieldTest extends TestCase
{
    public function test_type_is_morph_file(): void
    {
        $field = MorphFileField::make('avatar');

        self::assertSame('morph_file', $field->type());
    }

    public function test_morph_name_defaults_to_field_name(): void
    {
        $field = MorphFileField::make('avatar');

        self::assertSame('avatar', $field->getMorphName());
    }

    public function test_morph_name_setter_overrides_default(): void
    {
        $field = MorphFileField::make('avatar')->morphName('fileable');

        self::assertSame('fileable', $field->getMorphName());
    }

    public function test_stores_as_defaults_to_field_name(): void
    {
        $field = MorphFileField::make('avatar');

        self::assertSame('avatar', $field->getStoresAs());
    }

    public function test_stores_as_setter_overrides_default(): void
    {
        $field = MorphFileField::make('logo')->storesAs('brand_logo');

        self::assertSame('brand_logo', $field->getStoresAs());
    }

    public function test_file_model_getter_throws_when_not_configured(): void
    {
        $field = MorphFileField::make('avatar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/fileModel.*not configured|configure.*fileModel/i');

        $field->getFileModel();
    }

    public function test_file_model_setter_stores_class(): void
    {
        $field = MorphFileField::make('avatar')->fileModel('App\\Models\\File');

        self::assertSame('App\\Models\\File', $field->getFileModel());
    }

    public function test_directory_defaults_to_empty_string(): void
    {
        $field = MorphFileField::make('avatar');

        self::assertSame('', $field->getDirectory());
    }

    public function test_directory_setter_stores_value(): void
    {
        $field = MorphFileField::make('avatar')->directory('avatars');

        self::assertSame('avatars', $field->getDirectory());
    }

    public function test_disk_defaults_to_empty_string(): void
    {
        $field = MorphFileField::make('avatar');

        self::assertSame('', $field->getDisk());
    }

    public function test_disk_setter_stores_value(): void
    {
        $field = MorphFileField::make('avatar')->disk('s3');

        self::assertSame('s3', $field->getDisk());
    }

    public function test_fluent_chain_preserves_same_instance(): void
    {
        $field  = MorphFileField::make('avatar');
        $result = $field
            ->morphName('fileable')
            ->storesAs('avatar')
            ->fileModel('App\\Models\\File')
            ->directory('avatars')
            ->disk('s3');

        self::assertSame($field, $result);
    }

    public function test_fluent_chain_produces_all_expected_getters(): void
    {
        $field = MorphFileField::make('file')
            ->morphName('fileable')
            ->storesAs('attachment')
            ->fileModel('App\\Models\\File')
            ->directory('files/attachments')
            ->disk('public');

        self::assertSame('fileable', $field->getMorphName());
        self::assertSame('attachment', $field->getStoresAs());
        self::assertSame('App\\Models\\File', $field->getFileModel());
        self::assertSame('files/attachments', $field->getDirectory());
        self::assertSame('public', $field->getDisk());
    }

    public function test_rules_include_nullable_and_file(): void
    {
        // Повинен валідуватися як optional upload — перевіримо чи rules містять 'file' (якщо додано).
        $field = MorphFileField::make('avatar');
        $rules = $field->rules();

        self::assertContains('nullable', $rules);
        self::assertContains('file', $rules);
    }
}
