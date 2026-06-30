<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\MorphToField;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MorphToField::expandRules(array $locales, string $context): array
 *
 * Acceptance A6: MorphToField expands to TWO synthetic column keys — _type and _id —
 * each carrying domain-level marker rules that the Laravel adapter (Task 1.3) resolves
 * into real validators.
 *
 * EXACT MARKER STRINGS (pinned by this suite — must not change without updating Task 1.3):
 *   'morph_type_in:<FQCN1>,<FQCN2>,...'  — comma-separated allowed morph-type classes
 *   'morph_exists'                         — existence check for the morph target record
 *
 * Pre-IMPL status: expandRules() method does not exist → Error → RED.
 */
final class MorphToFieldExpandRulesTest extends TestCase
{
    // Pin the exact marker strings. Task 1.3 depends on these.
    private const MORPH_EXISTS = 'morph_exists';

    private function morphTypeInMarker(string ...$fqcns): string
    {
        return 'morph_type_in:' . implode(',', $fqcns);
    }

    // -------------------------------------------------------------------------
    // Key shape — two synthetic column keys
    // -------------------------------------------------------------------------

    public function test_expand_rules_emits_type_column_key(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'create');

        self::assertArrayHasKey('commentable_type', $result);
    }

    public function test_expand_rules_emits_id_column_key(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'create');

        self::assertArrayHasKey('commentable_id', $result);
    }

    public function test_expand_rules_emits_exactly_two_keys(): void
    {
        $field = MorphToField::make('taggable', 'App\\Models\\Tag');

        $result = $field->expandRules([], 'create');

        self::assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // 'create' context — required rule on both synthetic columns
    // -------------------------------------------------------------------------

    public function test_expand_rules_create_type_column_has_required(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'create');

        self::assertContains('required', $result['commentable_type']);
    }

    public function test_expand_rules_create_id_column_has_required(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'create');

        self::assertContains('required', $result['commentable_id']);
    }

    // -------------------------------------------------------------------------
    // Exact marker string assertions — pinned for Task 1.3
    // -------------------------------------------------------------------------

    public function test_expand_rules_id_column_contains_morph_exists_marker(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'create');

        self::assertContains(self::MORPH_EXISTS, $result['commentable_id']);
    }

    public function test_expand_rules_morph_exists_marker_exact_string_no_parameters(): void
    {
        // Exact string: 'morph_exists' — no colon, no parameters.
        $field = MorphToField::make('imageable', 'App\\Models\\Image');

        $result = $field->expandRules([], 'create');

        self::assertContains('morph_exists', $result['imageable_id']);
    }

    public function test_expand_rules_type_column_contains_morph_type_in_marker_with_fqcns(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment')->morphTypes([
            'App\\Models\\Book'   => 'Book',
            'App\\Models\\Review' => 'Review',
        ]);

        $result = $field->expandRules([], 'create');

        $expectedMarker = $this->morphTypeInMarker('App\\Models\\Book', 'App\\Models\\Review');
        self::assertContains($expectedMarker, $result['commentable_type']);
    }

    public function test_expand_rules_morph_type_in_marker_exact_format(): void
    {
        // EXACT FORMAT: 'morph_type_in:<FQCN1>,<FQCN2>' — no spaces, comma-separated,
        // FQCNs in the same order as morphTypeMap keys (insertion order).
        $field = MorphToField::make('imageable', 'App\\Models\\Image')->morphTypes([
            'App\\Models\\Post' => 'Post',
            'App\\Models\\User' => 'User',
        ]);

        $result = $field->expandRules([], 'create');

        self::assertContains(
            'morph_type_in:App\\Models\\Post,App\\Models\\User',
            $result['imageable_type'],
        );
    }

    public function test_expand_rules_morph_type_in_marker_with_no_morph_types_configured(): void
    {
        // When no morphTypes() are declared the map is empty.
        // The marker still appears but with an empty type list: 'morph_type_in:'.
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'create');

        // A marker with prefix 'morph_type_in:' must be present on the type column
        $typeRules = $result['commentable_type'];
        $hasMorphTypeIn = array_filter($typeRules, fn(string $r): bool => str_starts_with($r, 'morph_type_in:'));
        self::assertNotEmpty($hasMorphTypeIn, "type column must contain a 'morph_type_in:...' marker");
    }

    // -------------------------------------------------------------------------
    // 'update' context — synthetic columns get 'nullable' instead of 'required'
    // -------------------------------------------------------------------------

    public function test_expand_rules_update_context_type_column_not_required(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'update');

        self::assertNotContains('required', $result['commentable_type']);
    }

    public function test_expand_rules_update_context_id_column_not_required(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'update');

        self::assertNotContains('required', $result['commentable_id']);
    }

    public function test_expand_rules_update_context_type_column_has_nullable(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'update');

        self::assertContains('nullable', $result['commentable_type']);
    }

    public function test_expand_rules_update_context_id_column_has_nullable(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'update');

        self::assertContains('nullable', $result['commentable_id']);
    }

    public function test_expand_rules_update_context_still_has_morph_exists_on_id(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $result = $field->expandRules([], 'update');

        self::assertContains(self::MORPH_EXISTS, $result['commentable_id']);
    }

    // -------------------------------------------------------------------------
    // Locale-agnostic — MorphToField does not expand per-locale
    // -------------------------------------------------------------------------

    public function test_expand_rules_locales_do_not_affect_key_shape(): void
    {
        // MorphToField is not locale-aware; always emits exactly two keys.
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');

        $withLocales    = $field->expandRules(['en', 'uk'], 'create');
        $withoutLocales = $field->expandRules([], 'create');

        self::assertSame(array_keys($withLocales), array_keys($withoutLocales));
        self::assertCount(2, $withLocales);
    }
}
