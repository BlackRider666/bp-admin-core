<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasOneField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphManyField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphToField;
use PHPUnit\Framework\TestCase;

final class RelationFieldsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // BelongsToField
    // -------------------------------------------------------------------------

    public function test_belongs_to_field_make_creates_instance(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');

        self::assertInstanceOf(BelongsToField::class, $field);
    }

    public function test_belongs_to_field_type_returns_belongs_to(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');

        self::assertSame('belongs_to', $field->type());
    }

    public function test_belongs_to_field_relation_kind_returns_belongs_to(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');

        self::assertSame('belongsTo', $field->relationKind());
    }

    public function test_belongs_to_field_target_returns_model_class(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');

        self::assertSame('App\\Models\\User', $field->target());
    }

    public function test_belongs_to_field_multiple_returns_false_by_default(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');

        self::assertFalse($field->multiple());
    }

    public function test_belongs_to_field_create_inline_returns_false_by_default(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');

        self::assertFalse($field->createInline());
    }

    public function test_belongs_to_field_name_is_set_correctly(): void
    {
        $field = BelongsToField::make('author', 'App\\Models\\User');

        self::assertSame('author', $field->name());
    }

    public function test_belongs_to_field_inherits_abstract_field_fluent_api(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')
            ->withLabel('Owner')
            ->required()
            ->hideFromList();

        self::assertSame('Owner', $field->label());
        self::assertContains('required', $field->rules());
        self::assertFalse($field->visibleOnList());
    }

    // -------------------------------------------------------------------------
    // HasOneField
    // -------------------------------------------------------------------------

    public function test_has_one_field_type_returns_has_one(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile');

        self::assertSame('has_one', $field->type());
    }

    public function test_has_one_field_relation_kind_returns_has_one(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile');

        self::assertSame('hasOne', $field->relationKind());
    }

    public function test_has_one_field_target_returns_model_class(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile');

        self::assertSame('App\\Models\\Profile', $field->target());
    }

    public function test_has_one_field_multiple_returns_false_by_default(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile');

        self::assertFalse($field->multiple());
    }

    // -------------------------------------------------------------------------
    // HasManyField
    // -------------------------------------------------------------------------

    public function test_has_many_field_type_returns_has_many(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        self::assertSame('has_many', $field->type());
    }

    public function test_has_many_field_relation_kind_returns_has_many(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        self::assertSame('hasMany', $field->relationKind());
    }

    public function test_has_many_field_target_returns_model_class(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        self::assertSame('App\\Models\\Comment', $field->target());
    }

    // -------------------------------------------------------------------------
    // BelongsToManyField
    // -------------------------------------------------------------------------

    public function test_belongs_to_many_field_type_returns_belongs_to_many(): void
    {
        $field = BelongsToManyField::make('roles', 'App\\Models\\Role');

        self::assertSame('belongs_to_many', $field->type());
    }

    public function test_belongs_to_many_field_relation_kind_returns_belongs_to_many(): void
    {
        $field = BelongsToManyField::make('roles', 'App\\Models\\Role');

        self::assertSame('belongsToMany', $field->relationKind());
    }

    public function test_belongs_to_many_field_target_returns_model_class(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag');

        self::assertSame('App\\Models\\Tag', $field->target());
    }

    // -------------------------------------------------------------------------
    // MorphToField
    // -------------------------------------------------------------------------

    public function test_morph_to_field_type_returns_morph_to(): void
    {
        $field = MorphToField::make('imageable', 'App\\Models\\Image');

        self::assertSame('morph_to', $field->type());
    }

    public function test_morph_to_field_relation_kind_returns_morph_to(): void
    {
        $field = MorphToField::make('imageable', 'App\\Models\\Image');

        self::assertSame('morphTo', $field->relationKind());
    }

    public function test_morph_to_field_target_returns_model_class(): void
    {
        $field = MorphToField::make('imageable', 'App\\Models\\Image');

        self::assertSame('App\\Models\\Image', $field->target());
    }

    // -------------------------------------------------------------------------
    // MorphManyField
    // -------------------------------------------------------------------------

    public function test_morph_many_field_type_returns_morph_many(): void
    {
        $field = MorphManyField::make('images', 'App\\Models\\Image');

        self::assertSame('morph_many', $field->type());
    }

    public function test_morph_many_field_relation_kind_returns_morph_many(): void
    {
        $field = MorphManyField::make('images', 'App\\Models\\Image');

        self::assertSame('morphMany', $field->relationKind());
    }

    public function test_morph_many_field_target_returns_model_class(): void
    {
        $field = MorphManyField::make('media', 'App\\Models\\Media');

        self::assertSame('App\\Models\\Media', $field->target());
    }

    // -------------------------------------------------------------------------
    // Shared relation field defaults
    // -------------------------------------------------------------------------

    /**
     * @dataProvider relationFieldProvider
     */
    public function test_all_relation_fields_have_correct_default_visibility(
        string $class,
        string $name,
        string $target,
    ): void {
        $field = $class::make($name, $target);

        self::assertTrue($field->visibleOnList());
        self::assertTrue($field->visibleOnForm());
        self::assertTrue($field->visibleOnShow());
    }

    /**
     * @dataProvider relationFieldProvider
     */
    public function test_all_relation_fields_have_create_inline_false_by_default(
        string $class,
        string $name,
        string $target,
    ): void {
        $field = $class::make($name, $target);

        self::assertFalse($field->createInline());
    }

    public static function relationFieldProvider(): array
    {
        return [
            'BelongsToField' => [BelongsToField::class, 'user', 'App\\Models\\User'],
            'HasOneField' => [HasOneField::class, 'profile', 'App\\Models\\Profile'],
            'HasManyField' => [HasManyField::class, 'comments', 'App\\Models\\Comment'],
            'BelongsToManyField' => [BelongsToManyField::class, 'roles', 'App\\Models\\Role'],
            'MorphToField' => [MorphToField::class, 'imageable', 'App\\Models\\Image'],
            'MorphManyField' => [MorphManyField::class, 'images', 'App\\Models\\Image'],
        ];
    }

    // -------------------------------------------------------------------------
    // HasOneField::withForeignKey / getForeignKey
    // -------------------------------------------------------------------------

    public function test_has_one_field_default_foreign_key_is_null(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile');
        self::assertNull($field->getForeignKey());
    }

    public function test_has_one_field_with_foreign_key_setter(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile')->withForeignKey('owner_id');
        self::assertSame('owner_id', $field->getForeignKey());
    }
}
