<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\RuleBuilder;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\MorphToField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use PHPUnit\Framework\TestCase;

/**
 * Covers A4, A6: RuleBuilder::build() delegates rule expansion to each field's
 * expandRules() method, producing an instanceof-free loop.
 *
 * Focus: MorphToField integration (NEW — not handled by current build()).
 * Translatable field regression is already covered by RuleBuilderTest; it is
 * NOT duplicated here to avoid creating pre-green entries.
 *
 * Pre-IMPL status: build() does not call expandRules(); MorphToField's synthetic
 * keys ('commentable_type', 'commentable_id') are absent from the result →
 * assertArrayHasKey FAILS → RED.
 */
final class RuleBuilderExpandRulesTest extends TestCase
{
    /** @param array<int, FieldContract> $fields */
    private function makeDefinition(array $fields): EntityDefinitionContract
    {
        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('fields')->willReturn($fields);
        return $def;
    }

    // -------------------------------------------------------------------------
    // MorphToField integration — completely new behaviour in build()
    // -------------------------------------------------------------------------

    public function test_build_includes_morph_type_column_key(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');
        $def   = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder())->build($def);

        self::assertArrayHasKey('commentable_type', $rules);
    }

    public function test_build_includes_morph_id_column_key(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');
        $def   = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder())->build($def);

        self::assertArrayHasKey('commentable_id', $rules);
    }

    public function test_build_morph_type_has_required_in_create_context(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');
        $def   = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder([], 'create'))->build($def);

        self::assertContains('required', $rules['commentable_type']);
    }

    public function test_build_morph_id_has_required_in_create_context(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');
        $def   = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder([], 'create'))->build($def);

        self::assertContains('required', $rules['commentable_id']);
    }

    public function test_build_morph_type_contains_morph_type_in_marker(): void
    {
        // Exact marker format pinned here — see also MorphToFieldExpandRulesTest.
        $field = MorphToField::make('commentable', 'App\\Models\\Comment')->morphTypes([
            'App\\Models\\Book'   => 'Book',
            'App\\Models\\Review' => 'Review',
        ]);
        $def = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder())->build($def);

        self::assertContains(
            'morph_type_in:App\\Models\\Book,App\\Models\\Review',
            $rules['commentable_type'],
        );
    }

    public function test_build_morph_id_contains_morph_exists_marker(): void
    {
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');
        $def   = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder())->build($def);

        self::assertContains('morph_exists', $rules['commentable_id']);
    }

    public function test_build_morph_update_context_absent_key_relaxes_required(): void
    {
        // In update context with morph columns absent from presentKeys,
        // 'required' must not appear on the synthetic morph columns.
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');
        $def   = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder([], 'update'))->build($def, presentKeys: []);

        // The type column must be present (MorphToField always expands to two keys)
        self::assertArrayHasKey('commentable_type', $rules);
        self::assertNotContains('required', $rules['commentable_type']);
    }

    // -------------------------------------------------------------------------
    // Mixed definition — plain + translatable + morph all together
    // -------------------------------------------------------------------------

    public function test_build_handles_mixed_field_types_via_expand_rules(): void
    {
        $slugField        = TextField::make('slug')->required();
        $titleField       = TranslatableField::make('title')->required();
        $commentableField = MorphToField::make('commentable', 'App\\Models\\Comment');

        $def   = $this->makeDefinition([$slugField, $titleField, $commentableField]);
        $rules = (new RuleBuilder(['en']))->build($def);

        // Plain scalar field — single key
        self::assertArrayHasKey('slug', $rules);
        self::assertContains('required', $rules['slug']);

        // TranslatableField — locale-expanded key; flat key absent
        self::assertArrayHasKey('title.en', $rules);
        self::assertArrayNotHasKey('title', $rules);

        // MorphToField — synthetic _type and _id columns
        self::assertArrayHasKey('commentable_type', $rules);
        self::assertArrayHasKey('commentable_id', $rules);
    }

    public function test_build_morph_field_does_not_produce_its_own_name_as_key(): void
    {
        // MorphToField must expand to _type/_id synthetic keys, NOT the raw field name.
        // Pre-IMPL: build() ignores MorphToField → $rules is [] → assertArrayHasKey fails → RED.
        $field = MorphToField::make('commentable', 'App\\Models\\Comment');
        $def   = $this->makeDefinition([$field]);

        $rules = (new RuleBuilder())->build($def);

        // Synthetic _type column must be present (pre-IMPL: FAIL → RED)
        self::assertArrayHasKey('commentable_type', $rules);
        // Raw field name must NOT appear
        self::assertArrayNotHasKey('commentable', $rules);
    }
}
