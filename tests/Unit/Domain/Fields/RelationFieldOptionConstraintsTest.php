<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use PHPUnit\Framework\TestCase;

/**
 * Bug #7 — BelongsToMany (and all relation fields) option-scope API.
 *
 * Tests that whereOption() / scopeOptions() accumulate constraints that the
 * infrastructure provider can read via optionConstraints().
 */
final class RelationFieldOptionConstraintsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Default state
    // -------------------------------------------------------------------------

    public function test_option_constraints_empty_by_default(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag');

        self::assertSame([], $field->optionConstraints());
    }

    public function test_belongs_to_field_option_constraints_empty_by_default(): void
    {
        $field = BelongsToField::make('city_id', 'App\\Models\\City');

        self::assertSame([], $field->optionConstraints());
    }

    // -------------------------------------------------------------------------
    // whereOption()
    // -------------------------------------------------------------------------

    public function test_where_option_adds_single_constraint(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->whereOption('type', 'article');

        $constraints = $field->optionConstraints();

        self::assertCount(1, $constraints);
        self::assertSame('type', $constraints[0]['column']);
        self::assertSame('article', $constraints[0]['value']);
    }

    public function test_where_option_with_integer_value(): void
    {
        $field = BelongsToField::make('city_id', 'App\\Models\\City')
            ->whereOption('country_id', 1);

        $constraints = $field->optionConstraints();

        self::assertCount(1, $constraints);
        self::assertSame('country_id', $constraints[0]['column']);
        self::assertSame(1, $constraints[0]['value']);
    }

    public function test_where_option_with_boolean_value(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->whereOption('active', true);

        self::assertSame(true, $field->optionConstraints()[0]['value']);
    }

    public function test_where_option_with_null_value(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->whereOption('deleted_at', null);

        $constraints = $field->optionConstraints();
        self::assertCount(1, $constraints);
        self::assertNull($constraints[0]['value']);
    }

    public function test_where_option_accumulates_multiple_constraints(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->whereOption('type', 'article')
            ->whereOption('active', true);

        $constraints = $field->optionConstraints();

        self::assertCount(2, $constraints);
        self::assertSame('type', $constraints[0]['column']);
        self::assertSame('active', $constraints[1]['column']);
    }

    public function test_where_option_returns_same_instance_for_fluent_chaining(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag');
        $result = $field->whereOption('active', true);

        self::assertSame($field, $result);
    }

    // -------------------------------------------------------------------------
    // scopeOptions()
    // -------------------------------------------------------------------------

    public function test_scope_options_adds_multiple_constraints_at_once(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->scopeOptions(['type' => 'article', 'active' => true]);

        $constraints = $field->optionConstraints();

        self::assertCount(2, $constraints);

        $byColumn = array_column($constraints, 'value', 'column');
        self::assertSame('article', $byColumn['type']);
        self::assertSame(true, $byColumn['active']);
    }

    public function test_scope_options_with_empty_array_adds_no_constraints(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->scopeOptions([]);

        self::assertSame([], $field->optionConstraints());
    }

    public function test_scope_options_returns_same_instance_for_fluent_chaining(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag');
        $result = $field->scopeOptions(['active' => true]);

        self::assertSame($field, $result);
    }

    public function test_scope_options_and_where_option_accumulate_together(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->scopeOptions(['type' => 'article'])
            ->whereOption('active', true);

        $constraints = $field->optionConstraints();

        self::assertCount(2, $constraints);
        self::assertSame('type', $constraints[0]['column']);
        self::assertSame('active', $constraints[1]['column']);
    }

    // -------------------------------------------------------------------------
    // Constraints accessible through RelationFieldContract interface
    // -------------------------------------------------------------------------

    public function test_option_constraints_accessible_via_relation_field_contract(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->whereOption('country_id', 5);

        // Access through interface type
        $contract = $field;
        self::assertCount(1, $contract->optionConstraints());
        self::assertSame(5, $contract->optionConstraints()[0]['value']);
    }

    // -------------------------------------------------------------------------
    // Other relation field types also support constraints
    // -------------------------------------------------------------------------

    public function test_belongs_to_field_supports_where_option(): void
    {
        $field = BelongsToField::make('city_id', 'App\\Models\\City')
            ->whereOption('country_id', 1)
            ->whereOption('active', true);

        self::assertCount(2, $field->optionConstraints());
    }

    public function test_has_many_field_supports_scope_options(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment')
            ->scopeOptions(['approved' => true]);

        self::assertCount(1, $field->optionConstraints());
        self::assertSame('approved', $field->optionConstraints()[0]['column']);
    }

    // -------------------------------------------------------------------------
    // Constraint shape validation
    // -------------------------------------------------------------------------

    public function test_each_constraint_has_column_and_value_keys(): void
    {
        $field = BelongsToManyField::make('roles', 'App\\Models\\Role')
            ->whereOption('active', true)
            ->scopeOptions(['type' => 'admin']);

        foreach ($field->optionConstraints() as $constraint) {
            self::assertArrayHasKey('column', $constraint);
            self::assertArrayHasKey('value', $constraint);
        }
    }
}
