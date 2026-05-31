<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasOneField;
use PHPUnit\Framework\TestCase;

/**
 * Tests for relationName() and displayField() on relation fields.
 *
 * Covers:
 * - belongsTo convention: strip _id suffix when present
 * - belongsTo convention: use name as-is when no _id suffix
 * - non-belongsTo kinds: always use field name as-is
 * - explicit withRelationName overrides convention
 * - displayField defaults to 'name'
 * - explicit withDisplayField overrides default
 * - all fluent setters return same instance
 */
final class RelationFieldEagerLoadTest extends TestCase
{
    // -------------------------------------------------------------------------
    // relationName() — belongsTo _id stripping convention
    // -------------------------------------------------------------------------

    public function test_belongs_to_field_with_id_suffix_strips_suffix_for_relation_name(): void
    {
        $field = BelongsToField::make('category_id', 'App\\Models\\Category');

        self::assertSame('category', $field->relationName());
    }

    public function test_belongs_to_field_with_user_id_suffix_strips_suffix(): void
    {
        $field = BelongsToField::make('user_id', 'App\\Models\\User');

        self::assertSame('user', $field->relationName());
    }

    public function test_belongs_to_field_without_id_suffix_uses_name_as_is(): void
    {
        $field = BelongsToField::make('category', 'App\\Models\\Category');

        self::assertSame('category', $field->relationName());
    }

    public function test_belongs_to_field_with_name_that_happens_to_not_end_in_id(): void
    {
        $field = BelongsToField::make('parent_category', 'App\\Models\\Category');

        self::assertSame('parent_category', $field->relationName());
    }

    // -------------------------------------------------------------------------
    // relationName() — non-belongsTo kinds always use field name as-is
    // -------------------------------------------------------------------------

    public function test_has_many_field_uses_field_name_as_relation_name(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        self::assertSame('comments', $field->relationName());
    }

    public function test_has_one_field_uses_field_name_as_relation_name(): void
    {
        $field = HasOneField::make('profile', 'App\\Models\\Profile');

        self::assertSame('profile', $field->relationName());
    }

    public function test_belongs_to_many_field_uses_field_name_as_relation_name(): void
    {
        $field = BelongsToManyField::make('roles', 'App\\Models\\Role');

        self::assertSame('roles', $field->relationName());
    }

    public function test_has_many_field_with_id_suffix_does_not_strip_it(): void
    {
        // Only belongsTo should strip _id — hasMany with _id is unusual but must not strip
        $field = HasManyField::make('order_id', 'App\\Models\\Order');

        self::assertSame('order_id', $field->relationName());
    }

    // -------------------------------------------------------------------------
    // withRelationName() — explicit override
    // -------------------------------------------------------------------------

    public function test_explicit_with_relation_name_overrides_belongs_to_convention(): void
    {
        $field = BelongsToField::make('category_id', 'App\\Models\\Category')
            ->withRelationName('productCategory');

        self::assertSame('productCategory', $field->relationName());
    }

    public function test_explicit_with_relation_name_overrides_has_many_default(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment')
            ->withRelationName('publishedComments');

        self::assertSame('publishedComments', $field->relationName());
    }

    public function test_with_relation_name_returns_same_instance(): void
    {
        $field = BelongsToField::make('user_id', 'App\\Models\\User');
        $result = $field->withRelationName('owner');

        self::assertSame($field, $result);
        self::assertInstanceOf(BelongsToField::class, $result);
    }

    // -------------------------------------------------------------------------
    // displayField() — default and override
    // -------------------------------------------------------------------------

    public function test_display_field_defaults_to_name(): void
    {
        $field = BelongsToField::make('category_id', 'App\\Models\\Category');

        self::assertSame('name', $field->displayField());
    }

    public function test_display_field_default_applies_to_has_many(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        self::assertSame('name', $field->displayField());
    }

    public function test_with_display_field_overrides_default(): void
    {
        $field = BelongsToField::make('category_id', 'App\\Models\\Category')
            ->withDisplayField('title');

        self::assertSame('title', $field->displayField());
    }

    public function test_with_display_field_returns_same_instance(): void
    {
        $field = BelongsToField::make('user_id', 'App\\Models\\User');
        $result = $field->withDisplayField('email');

        self::assertSame($field, $result);
        self::assertInstanceOf(BelongsToField::class, $result);
    }

    // -------------------------------------------------------------------------
    // Full fluent chain
    // -------------------------------------------------------------------------

    public function test_full_fluent_chain_works_and_returns_correct_values(): void
    {
        $field = BelongsToField::make('category_id', 'App\\Models\\Category')
            ->withRelationName('mainCategory')
            ->withDisplayField('title')
            ->required()
            ->withLabel('Main category');

        self::assertSame('mainCategory', $field->relationName());
        self::assertSame('title', $field->displayField());
        self::assertContains('required', $field->rules());
        self::assertSame('Main category', $field->label());
    }

    public function test_fluent_chain_with_belongs_to_many_and_display_field(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->withDisplayField('name')
            ->withRelationName('articleTags');

        self::assertSame('articleTags', $field->relationName());
        self::assertSame('name', $field->displayField());
    }
}
