<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\FileField;
use BlackParadise\CoreAdmin\Domain\Fields\ImageField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphFileField;
use PHPUnit\Framework\TestCase;

final class FileFieldAllowlistTest extends TestCase
{
    public function test_image_field_has_safe_default_extension_allowlist(): void
    {
        $field = ImageField::make('avatar');
        self::assertSame(['jpg','jpeg','png','gif','webp'], $field->getAllowedExtensions());
    }

    public function test_image_field_default_excludes_svg(): void
    {
        $field = ImageField::make('avatar');
        self::assertNotContains('svg', $field->getAllowedExtensions());
    }

    public function test_file_field_has_safe_default_allowlist(): void
    {
        $field = FileField::make('attachment');
        self::assertSame(['pdf','doc','docx','xls','xlsx','txt','csv','zip'], $field->getAllowedExtensions());
    }

    public function test_morph_file_field_has_safe_default_allowlist(): void
    {
        $field = MorphFileField::make('attachment');
        self::assertSame(['pdf','doc','docx','xls','xlsx','txt','csv','zip'], $field->getAllowedExtensions());
    }

    public function test_allowed_extensions_can_be_overridden(): void
    {
        $field = FileField::make('doc')->allowedExtensions(['pdf']);
        self::assertSame(['pdf'], $field->getAllowedExtensions());
    }

    public function test_allowed_mimes_can_be_overridden(): void
    {
        $field = FileField::make('doc')->allowedMimes(['application/pdf']);
        self::assertSame(['application/pdf'], $field->getAllowedMimes());
    }

    public function test_rules_include_mimes_constraint_when_extensions_set(): void
    {
        $field = FileField::make('doc')->allowedExtensions(['pdf','doc']);
        $rules = $field->rules();
        self::assertContains('mimes:pdf,doc', $rules);
    }

    public function test_rules_include_mimetypes_constraint_when_mimes_set(): void
    {
        $field = FileField::make('doc')->allowedMimes(['application/pdf']);
        $rules = $field->rules();
        self::assertContains('mimetypes:application/pdf', $rules);
    }

    public function test_php_extension_is_rejected_in_default_image_allowlist(): void
    {
        $field = ImageField::make('avatar');
        self::assertNotContains('php', $field->getAllowedExtensions());
    }

    public function test_allowed_extensions_preserved_when_with_rules_called(): void
    {
        $field = FileField::make('doc')
            ->allowedExtensions(['pdf'])
            ->withRules(['required']);
        $rules = $field->rules();
        self::assertContains('required', $rules);
        self::assertContains('mimes:pdf', $rules);
    }

    public function test_empty_extension_allowlist_does_not_add_mimes_constraint(): void
    {
        $field = FileField::make('doc')->allowedExtensions([]);
        $rules = $field->rules();
        foreach ($rules as $r) {
            self::assertStringStartsNotWith('mimes:', is_string($r) ? $r : '');
        }
    }

    public function test_clearing_allowlist_via_empty_array(): void
    {
        $field = FileField::make('doc')
            ->allowedExtensions(['pdf'])
            ->allowedExtensions([]);
        self::assertSame([], $field->getAllowedExtensions());
        $rules = $field->rules();
        foreach ($rules as $r) {
            self::assertStringStartsNotWith('mimes:', is_string($r) ? $r : '');
        }
    }

    public function test_array_values_reindexing_for_allowed_extensions(): void
    {
        $field = FileField::make('doc')->allowedExtensions(['a' => 'pdf', 'b' => 'doc']);
        self::assertSame(['pdf', 'doc'], $field->getAllowedExtensions());
    }
}
