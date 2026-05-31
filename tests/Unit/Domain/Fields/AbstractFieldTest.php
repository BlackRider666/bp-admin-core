<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;

/**
 * Tests the AbstractField fluent API through the concrete TextField implementation.
 */
final class AbstractFieldTest extends TestCase
{
    public function test_make_creates_instance_with_given_name(): void
    {
        $field = TextField::make('first_name');

        self::assertSame('first_name', $field->name());
    }

    public function test_type_returns_text(): void
    {
        $field = TextField::make('name');

        self::assertSame('text', $field->type());
    }

    public function test_label_auto_generates_from_name_with_underscores(): void
    {
        $field = TextField::make('first_name');

        self::assertSame('First name', $field->label());
    }

    public function test_label_auto_generates_from_simple_name(): void
    {
        $field = TextField::make('email');

        self::assertSame('Email', $field->label());
    }

    public function test_with_label_overrides_auto_generated_label(): void
    {
        $field = TextField::make('first_name')->withLabel('Given Name');

        self::assertSame('Given Name', $field->label());
    }

    public function test_with_label_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->withLabel('Full Name');

        self::assertSame($field, $result);
        self::assertInstanceOf(TextField::class, $result);
    }

    public function test_rules_returns_empty_array_by_default(): void
    {
        $field = TextField::make('name');

        self::assertSame([], $field->rules());
    }

    public function test_with_rules_replaces_rules_array(): void
    {
        $field = TextField::make('name')->withRules(['required', 'max:255']);

        self::assertSame(['required', 'max:255'], $field->rules());
    }

    public function test_with_rules_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->withRules(['required']);

        self::assertSame($field, $result);
    }

    public function test_required_adds_required_rule(): void
    {
        $field = TextField::make('name')->required();

        self::assertContains('required', $field->rules());
    }

    public function test_required_does_not_duplicate_required_rule(): void
    {
        $field = TextField::make('name')->required()->required();

        self::assertSame(['required'], $field->rules());
    }

    public function test_required_removes_nullable_rule(): void
    {
        $field = TextField::make('name')->nullable()->required();

        self::assertNotContains('nullable', $field->rules());
        self::assertContains('required', $field->rules());
    }

    public function test_required_false_removes_required_rule(): void
    {
        $field = TextField::make('name')->required()->required(false);

        self::assertNotContains('required', $field->rules());
    }

    public function test_required_false_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->required(false);

        self::assertSame($field, $result);
    }

    public function test_nullable_adds_nullable_rule(): void
    {
        $field = TextField::make('name')->nullable();

        self::assertContains('nullable', $field->rules());
    }

    public function test_nullable_removes_required_rule(): void
    {
        $field = TextField::make('name')->required()->nullable();

        self::assertNotContains('required', $field->rules());
        self::assertContains('nullable', $field->rules());
    }

    public function test_nullable_does_not_duplicate_nullable_rule(): void
    {
        $field = TextField::make('name')->nullable()->nullable();

        self::assertSame(['nullable'], $field->rules());
    }

    public function test_nullable_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->nullable();

        self::assertSame($field, $result);
    }

    public function test_visible_on_list_returns_true_by_default(): void
    {
        $field = TextField::make('name');

        self::assertTrue($field->visibleOnList());
    }

    public function test_hide_from_list_sets_visible_on_list_to_false(): void
    {
        $field = TextField::make('name')->hideFromList();

        self::assertFalse($field->visibleOnList());
    }

    public function test_show_on_list_sets_visible_on_list_to_true(): void
    {
        $field = TextField::make('name')->hideFromList()->showOnList();

        self::assertTrue($field->visibleOnList());
    }

    public function test_hide_from_list_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->hideFromList();

        self::assertSame($field, $result);
    }

    public function test_show_on_list_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->showOnList();

        self::assertSame($field, $result);
    }

    public function test_visible_on_form_returns_true_by_default(): void
    {
        $field = TextField::make('name');

        self::assertTrue($field->visibleOnForm());
    }

    public function test_hide_from_form_sets_visible_on_form_to_false(): void
    {
        $field = TextField::make('name')->hideFromForm();

        self::assertFalse($field->visibleOnForm());
    }

    public function test_show_on_form_sets_visible_on_form_to_true(): void
    {
        $field = TextField::make('name')->hideFromForm()->showOnForm();

        self::assertTrue($field->visibleOnForm());
    }

    public function test_hide_from_form_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->hideFromForm();

        self::assertSame($field, $result);
    }

    public function test_show_on_form_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->showOnForm();

        self::assertSame($field, $result);
    }

    public function test_visible_on_show_returns_true_by_default(): void
    {
        $field = TextField::make('name');

        self::assertTrue($field->visibleOnShow());
    }

    public function test_hide_from_show_sets_visible_on_show_to_false(): void
    {
        $field = TextField::make('name')->hideFromShow();

        self::assertFalse($field->visibleOnShow());
    }

    public function test_show_on_show_sets_visible_on_show_to_true(): void
    {
        $field = TextField::make('name')->hideFromShow()->showOnShow();

        self::assertTrue($field->visibleOnShow());
    }

    public function test_hide_from_show_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->hideFromShow();

        self::assertSame($field, $result);
    }

    public function test_show_on_show_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->showOnShow();

        self::assertSame($field, $result);
    }

    public function test_is_sortable_returns_false_by_default(): void
    {
        $field = TextField::make('name');

        self::assertFalse($field->isSortable());
    }

    public function test_sortable_enables_sorting(): void
    {
        $field = TextField::make('name')->sortable();

        self::assertTrue($field->isSortable());
    }

    public function test_sortable_false_disables_sorting(): void
    {
        $field = TextField::make('name')->sortable()->sortable(false);

        self::assertFalse($field->isSortable());
    }

    public function test_sortable_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->sortable();

        self::assertSame($field, $result);
    }

    public function test_is_filterable_returns_false_by_default(): void
    {
        $field = TextField::make('name');

        self::assertFalse($field->isFilterable());
    }

    public function test_filterable_enables_filtering(): void
    {
        $field = TextField::make('name')->filterable();

        self::assertTrue($field->isFilterable());
    }

    public function test_filterable_false_disables_filtering(): void
    {
        $field = TextField::make('name')->filterable()->filterable(false);

        self::assertFalse($field->isFilterable());
    }

    public function test_filterable_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->filterable();

        self::assertSame($field, $result);
    }

    public function test_searchable_is_alias_for_filterable_true(): void
    {
        $field = TextField::make('name')->searchable();

        self::assertTrue($field->isFilterable());
    }

    public function test_searchable_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->searchable();

        self::assertSame($field, $result);
    }

    public function test_meta_returns_empty_array_by_default(): void
    {
        $field = TextField::make('name');

        self::assertSame([], $field->meta());
    }

    public function test_with_meta_merges_metadata(): void
    {
        $field = TextField::make('name')->withMeta(['placeholder' => 'Enter name']);

        self::assertSame(['placeholder' => 'Enter name'], $field->meta());
    }

    public function test_with_meta_merges_multiple_calls(): void
    {
        $field = TextField::make('name')
            ->withMeta(['placeholder' => 'Enter name'])
            ->withMeta(['maxlength' => 100]);

        self::assertSame(['placeholder' => 'Enter name', 'maxlength' => 100], $field->meta());
    }

    public function test_with_meta_returns_static_for_fluent_chaining(): void
    {
        $field = TextField::make('name');
        $result = $field->withMeta(['key' => 'value']);

        self::assertSame($field, $result);
    }

    public function test_fluent_chain_returns_same_instance(): void
    {
        $field = TextField::make('email')
            ->withLabel('Email Address')
            ->required()
            ->withRules(['required', 'email'])
            ->sortable()
            ->filterable()
            ->withMeta(['type' => 'email']);

        self::assertSame('email', $field->name());
        self::assertSame('Email Address', $field->label());
        self::assertContains('required', $field->rules());
        self::assertTrue($field->isSortable());
        self::assertTrue($field->isFilterable());
        self::assertSame(['type' => 'email'], $field->meta());
    }
}
