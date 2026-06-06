<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;

/**
 * A10 (V8): Default label humanizes camelCase field names.
 *
 * - snake_case behavior preserved: 'executive_editor' → 'Executive editor'
 * - camelCase humanized: 'executiveEditor' → 'Executive Editor'
 * - PascalCase humanized: 'ExecutiveEditor' → 'Executive Editor'
 *
 * Currently BUGS: label() only applies str_replace('_', ' ', ...) — camelCase
 * humps are not split, so 'executiveEditor' → 'executiveEditor' and
 * 'ExecutiveEditor' → 'ExecutiveEditor'.
 * These tests are RED against current code.
 */
final class FieldLabelHumanizeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // camelCase → humanized
    // -------------------------------------------------------------------------

    public function test_camel_case_field_name_is_humanized(): void
    {
        $field = new TextField('executiveEditor');

        self::assertSame(
            'Executive Editor',
            $field->label(),
            'camelCase field name must be split into "Executive Editor".',
        );
    }

    public function test_pascal_case_field_name_is_humanized(): void
    {
        $field = new TextField('ExecutiveEditor');

        self::assertSame(
            'Executive Editor',
            $field->label(),
            'PascalCase field name must be split into "Executive Editor".',
        );
    }

    public function test_camel_case_multi_word_field_name_is_humanized(): void
    {
        $field = new TextField('firstName');

        self::assertSame('First Name', $field->label());
    }

    public function test_camel_case_three_words_field_name_is_humanized(): void
    {
        $field = new TextField('dateOfBirth');

        self::assertSame('Date Of Birth', $field->label());
    }

    // -------------------------------------------------------------------------
    // snake_case behavior preserved
    // -------------------------------------------------------------------------

    public function test_snake_case_field_name_preserves_existing_behavior(): void
    {
        $field = new TextField('executive_editor');

        // Existing behavior: ucfirst + spaces from underscores → 'Executive editor'
        // (only first word capitalized via ucfirst).
        self::assertSame(
            'Executive editor',
            $field->label(),
            'snake_case behavior must be preserved: "Executive editor" (only first word ucfirst).',
        );
    }

    public function test_plain_lowercase_field_name_ucfirsted(): void
    {
        $field = new TextField('title');

        self::assertSame('Title', $field->label());
    }

    public function test_snake_case_three_words_preserves_existing_behavior(): void
    {
        $field = new TextField('first_name');

        self::assertSame('First name', $field->label());
    }

    // -------------------------------------------------------------------------
    // Explicit label overrides humanization
    // -------------------------------------------------------------------------

    public function test_explicit_label_is_not_humanized(): void
    {
        $field = (new TextField('executiveEditor'))->withLabel('My Custom Label');

        self::assertSame('My Custom Label', $field->label());
    }
}
