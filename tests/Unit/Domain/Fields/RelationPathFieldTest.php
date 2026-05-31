<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\RelationPathField;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RelationPathFieldTest extends TestCase
{
    public function test_name_is_the_full_path(): void
    {
        $field = RelationPathField::make('publication.title');

        self::assertSame('publication.title', $field->name());
    }

    public function test_path_returns_dot_path(): void
    {
        $field = RelationPathField::make('publication.title');

        self::assertSame('publication.title', $field->path());
    }

    public function test_type_is_relation_path(): void
    {
        self::assertSame('relation_path', RelationPathField::make('x.y')->type());
    }

    public function test_relation_prefix_returns_part_before_last_dot(): void
    {
        self::assertSame(
            'publication',
            RelationPathField::make('publication.title')->relationPrefix(),
        );
    }

    public function test_relation_prefix_handles_nested_path(): void
    {
        self::assertSame(
            'publication.author',
            RelationPathField::make('publication.author.name')->relationPrefix(),
        );
    }

    public function test_attribute_path_returns_last_segment(): void
    {
        self::assertSame(
            'title',
            RelationPathField::make('publication.title')->attributePath(),
        );
    }

    public function test_attribute_path_in_nested(): void
    {
        self::assertSame(
            'name',
            RelationPathField::make('publication.author.name')->attributePath(),
        );
    }

    public function test_field_without_dot_throws_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/dot|path/i');

        RelationPathField::make('just_a_column');
    }

    public function test_is_sortable_false_by_default(): void
    {
        $field = RelationPathField::make('publication.title');

        self::assertFalse($field->isSortable());
    }

    public function test_writable_is_false_by_default(): void
    {
        // RelationPathField is read-only by nature: the path resolves through
        // related models and has no column on the host to write to. The
        // mutator must strip any incoming value for this field.
        $field = RelationPathField::make('publication.title');

        self::assertFalse($field->writable());
    }
}
