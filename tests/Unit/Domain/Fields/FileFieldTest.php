<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\FileField;
use PHPUnit\Framework\TestCase;

final class FileFieldTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Default validation rules
    // -------------------------------------------------------------------------

    public function test_file_field_has_nullable_rule_by_default(): void
    {
        $field = FileField::make('document');

        self::assertContains('nullable', $field->rules());
    }

    public function test_file_field_has_file_rule_by_default(): void
    {
        $field = FileField::make('document');

        self::assertContains('file', $field->rules());
    }

    // -------------------------------------------------------------------------
    // directory() fluent method
    // -------------------------------------------------------------------------

    public function test_file_field_directory_defaults_to_empty(): void
    {
        $field = FileField::make('document');

        self::assertSame('', $field->getDirectory());
    }

    public function test_file_field_directory_fluent_api(): void
    {
        $field = FileField::make('document');
        $result = $field->directory('uploads/docs');

        self::assertSame($field, $result);
        self::assertSame('uploads/docs', $field->getDirectory());
    }

    // -------------------------------------------------------------------------
    // disk() fluent method
    // -------------------------------------------------------------------------

    public function test_file_field_disk_defaults_to_empty(): void
    {
        $field = FileField::make('document');

        self::assertSame('', $field->getDisk());
    }

    public function test_file_field_disk_fluent_api(): void
    {
        $field = FileField::make('document');
        $result = $field->disk('s3');

        self::assertSame($field, $result);
        self::assertSame('s3', $field->getDisk());
    }
}
