<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\MorphToField;
use PHPUnit\Framework\TestCase;

final class MorphToFieldTest extends TestCase
{
    public function test_morph_types_normalised_from_mixed_config(): void
    {
        $f = MorphToField::make('commentable', 'App\\Models\\Book')->morphTypes([
            'App\\Models\\Book'   => ['label' => 'Book', 'display' => 'title'],
            'App\\Models\\Review' => 'Review',
        ]);
        $map = $f->morphTypeMap();
        self::assertSame('Book', $map['App\\Models\\Book']['label']);
        self::assertSame('title', $map['App\\Models\\Book']['display']);
        self::assertSame('Review', $map['App\\Models\\Review']['label']);
        self::assertSame('name', $map['App\\Models\\Review']['display']); // default displayField
    }

    public function test_display_default_follows_with_display_field(): void
    {
        $f = MorphToField::make('commentable', 'App\\Models\\Book')
            ->withDisplayField('title')
            ->morphTypes(['App\\Models\\Book' => 'Book']);
        self::assertSame('title', $f->morphTypeMap()['App\\Models\\Book']['display']);
    }

    public function test_morph_columns(): void
    {
        $f = MorphToField::make('commentable', 'App\\Models\\Book');
        self::assertSame('commentable_type', $f->typeColumn());
        self::assertSame('commentable_id', $f->idColumn());
        self::assertSame(['commentable_type', 'commentable_id'], $f->morphColumns());
    }

    public function test_morph_types_is_fluent(): void
    {
        $f = MorphToField::make('commentable', 'App\\Models\\Book');
        self::assertSame($f, $f->morphTypes(['App\\Models\\Book' => 'Book']));
    }
}
