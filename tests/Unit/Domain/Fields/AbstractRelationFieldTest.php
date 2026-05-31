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

/**
 * Deep coverage for AbstractRelationField:
 * fluent API inheritance, createInline default, multiple() semantics,
 * and all visibility / rule methods accessible on concrete relation types.
 */
final class AbstractRelationFieldTest extends TestCase
{
    // -------------------------------------------------------------------------
    // createInline() default
    // -------------------------------------------------------------------------

    /**
     * @dataProvider allRelationFieldProvider
     */
    public function test_create_inline_is_false_by_default(string $class, string $name, string $target): void
    {
        $field = $class::make($name, $target);

        self::assertFalse($field->createInline());
    }

    // -------------------------------------------------------------------------
    // multiple() defaults per type
    // -------------------------------------------------------------------------

    public function test_belongs_to_field_multiple_is_false_by_default(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');

        self::assertFalse($field->multiple());
    }

    public function test_belongs_to_many_field_multiple_is_false_by_default(): void
    {
        // BelongsToManyField uses the same AbstractRelationField default (false).
        // The *semantic* intent of multiple cardinality is expressed via the type string,
        // not the multiple() flag, unless explicitly set.
        $field = BelongsToManyField::make('roles', 'App\\Models\\Role');

        self::assertFalse($field->multiple());
    }

    public function test_has_many_field_multiple_is_false_by_default(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        self::assertFalse($field->multiple());
    }

    public function test_morph_many_field_multiple_is_false_by_default(): void
    {
        $field = MorphManyField::make('images', 'App\\Models\\Image');

        self::assertFalse($field->multiple());
    }

    // -------------------------------------------------------------------------
    // Inherited AbstractField fluent API — withLabel
    // -------------------------------------------------------------------------

    public function test_with_label_sets_label_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')
            ->withLabel('Account owner');

        self::assertSame('Account owner', $field->label());
    }

    public function test_with_label_returns_same_instance(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');
        $result = $field->withLabel('Owner');

        self::assertSame($field, $result);
        self::assertInstanceOf(BelongsToField::class, $result);
    }

    // -------------------------------------------------------------------------
    // required() / nullable()
    // -------------------------------------------------------------------------

    public function test_required_adds_required_rule_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')->required();

        self::assertContains('required', $field->rules());
    }

    public function test_required_removes_nullable_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')
            ->nullable()
            ->required();

        self::assertNotContains('nullable', $field->rules());
        self::assertContains('required', $field->rules());
    }

    public function test_required_returns_same_instance_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');
        $result = $field->required();

        self::assertSame($field, $result);
    }

    public function test_nullable_adds_nullable_rule_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')->nullable();

        self::assertContains('nullable', $field->rules());
    }

    public function test_nullable_removes_required_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')
            ->required()
            ->nullable();

        self::assertNotContains('required', $field->rules());
        self::assertContains('nullable', $field->rules());
    }

    public function test_nullable_returns_same_instance_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');
        $result = $field->nullable();

        self::assertSame($field, $result);
    }

    // -------------------------------------------------------------------------
    // withRules()
    // -------------------------------------------------------------------------

    public function test_with_rules_replaces_rules_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')
            ->withRules(['exists:users,id']);

        self::assertSame(['exists:users,id'], $field->rules());
    }

    public function test_with_rules_returns_same_instance_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');
        $result = $field->withRules(['exists:users,id']);

        self::assertSame($field, $result);
    }

    // -------------------------------------------------------------------------
    // hideFromList / showOnList
    // -------------------------------------------------------------------------

    public function test_hide_from_list_works_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')->hideFromList();

        self::assertFalse($field->visibleOnList());
    }

    public function test_show_on_list_restores_visibility_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')
            ->hideFromList()
            ->showOnList();

        self::assertTrue($field->visibleOnList());
    }

    public function test_hide_from_list_returns_same_instance_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');
        $result = $field->hideFromList();

        self::assertSame($field, $result);
    }

    // -------------------------------------------------------------------------
    // hideFromForm / showOnForm
    // -------------------------------------------------------------------------

    public function test_hide_from_form_works_on_relation_field(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment')->hideFromForm();

        self::assertFalse($field->visibleOnForm());
    }

    public function test_show_on_form_restores_visibility_on_relation_field(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment')
            ->hideFromForm()
            ->showOnForm();

        self::assertTrue($field->visibleOnForm());
    }

    public function test_hide_from_form_returns_same_instance_on_relation_field(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');
        $result = $field->hideFromForm();

        self::assertSame($field, $result);
    }

    // -------------------------------------------------------------------------
    // hideFromShow / showOnShow
    // -------------------------------------------------------------------------

    public function test_hide_from_show_works_on_relation_field(): void
    {
        $field = MorphToField::make('imageable', 'App\\Models\\Image')->hideFromShow();

        self::assertFalse($field->visibleOnShow());
    }

    public function test_show_on_show_restores_visibility_on_relation_field(): void
    {
        $field = MorphToField::make('imageable', 'App\\Models\\Image')
            ->hideFromShow()
            ->showOnShow();

        self::assertTrue($field->visibleOnShow());
    }

    // -------------------------------------------------------------------------
    // sortable / filterable / searchable / withMeta
    // -------------------------------------------------------------------------

    public function test_sortable_works_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')->sortable();

        self::assertTrue($field->isSortable());
    }

    public function test_sortable_returns_same_instance_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');
        $result = $field->sortable();

        self::assertSame($field, $result);
    }

    public function test_filterable_works_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')->filterable();

        self::assertTrue($field->isFilterable());
    }

    public function test_searchable_is_alias_for_filterable_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')->searchable();

        self::assertTrue($field->isFilterable());
    }

    public function test_with_meta_merges_metadata_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User')
            ->withMeta(['display_field' => 'name']);

        self::assertSame(['display_field' => 'name'], $field->meta());
    }

    public function test_with_meta_returns_same_instance_on_relation_field(): void
    {
        $field = BelongsToField::make('user', 'App\\Models\\User');
        $result = $field->withMeta(['key' => 'value']);

        self::assertSame($field, $result);
    }

    // -------------------------------------------------------------------------
    // Full fluent chain on a relation field
    // -------------------------------------------------------------------------

    public function test_fluent_chain_returns_same_instance_on_belongs_to_field(): void
    {
        $field = BelongsToField::make('category', 'App\\Models\\Category')
            ->withLabel('Product category')
            ->required()
            ->withRules(['required', 'exists:categories,id'])
            ->sortable()
            ->filterable()
            ->withMeta(['display_field' => 'title'])
            ->hideFromList();

        self::assertSame('Product category', $field->label());
        self::assertContains('required', $field->rules());
        self::assertTrue($field->isSortable());
        self::assertTrue($field->isFilterable());
        self::assertSame(['display_field' => 'title'], $field->meta());
        self::assertFalse($field->visibleOnList());
    }

    public function test_fluent_chain_returns_same_instance_on_belongs_to_many_field(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->withLabel('Article tags')
            ->nullable()
            ->hideFromList()
            ->withMeta(['min' => 1]);

        self::assertSame('Article tags', $field->label());
        self::assertContains('nullable', $field->rules());
        self::assertFalse($field->visibleOnList());
        self::assertSame(['min' => 1], $field->meta());
    }

    // -------------------------------------------------------------------------
    // Label auto-generation on relation fields
    // -------------------------------------------------------------------------

    public function test_label_auto_generated_from_relation_name(): void
    {
        $field = BelongsToField::make('parent_category', 'App\\Models\\Category');

        self::assertSame('Parent category', $field->label());
    }

    // -------------------------------------------------------------------------
    // Providers
    // -------------------------------------------------------------------------

    public static function allRelationFieldProvider(): array
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
}
