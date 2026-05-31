<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\HasOneField;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Fixtures\StubEntityDefinition;

final class RelationFieldEmbedOwnsTest extends TestCase
{
    public function test_is_embedded_false_by_default(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');

        self::assertFalse($field->isEmbedded());
        self::assertNull($field->embeddedDefinition());
    }

    public function test_is_owned_false_by_default(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');

        self::assertFalse($field->isOwned());
    }

    public function test_embed_sets_flag_and_stores_definition_class(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEntityDefinition::class);

        self::assertTrue($field->isEmbedded());
        self::assertSame(StubEntityDefinition::class, $field->embeddedDefinition());
    }

    public function test_embed_returns_same_instance(): void
    {
        $field  = BelongsToField::make('author_id', 'App\\Models\\Author');
        $result = $field->embed(StubEntityDefinition::class);

        self::assertSame($field, $result);
    }

    public function test_owns_sets_flag(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author')->owns();

        self::assertTrue($field->isOwned());
    }

    public function test_owns_returns_same_instance(): void
    {
        $field  = HasOneField::make('profile', 'App\\Models\\Profile');
        $result = $field->owns();

        self::assertSame($field, $result);
    }

    public function test_embed_and_owns_are_orthogonal(): void
    {
        $embedOnly = BelongsToField::make('x', 'X')->embed(StubEntityDefinition::class);
        $ownsOnly  = BelongsToField::make('y', 'Y')->owns();
        $both      = BelongsToField::make('z', 'Z')->embed(StubEntityDefinition::class)->owns();

        self::assertTrue($embedOnly->isEmbedded());
        self::assertFalse($embedOnly->isOwned());

        self::assertFalse($ownsOnly->isEmbedded());
        self::assertTrue($ownsOnly->isOwned());

        self::assertTrue($both->isEmbedded());
        self::assertTrue($both->isOwned());
    }

    public function test_has_one_field_supports_embed_and_owns(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile')
            ->embed(StubEntityDefinition::class)
            ->owns();

        self::assertTrue($field->isEmbedded());
        self::assertTrue($field->isOwned());
        self::assertSame(StubEntityDefinition::class, $field->embeddedDefinition());
    }

    public function test_embed_throws_when_class_does_not_exist(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $field->embed('App\\Nope\\Missing');
    }

    public function test_embed_throws_when_class_is_not_entity_definition(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');
        $field->embed(stdClass::class);
    }
}
