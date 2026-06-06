<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\RuleBuilder;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;

/**
 * A7 (B17): RuleBuilder in 'update' context drops/relaxes 'required' for fields
 * absent from presentKeys; keeps 'required' for fields that are present.
 * 'create' context is unchanged (always emits 'required').
 *
 * Currently BUGS: RuleBuilder has no context or presentKeys concept,
 * so these tests are RED against current code.
 */
final class RuleBuilderContextTest extends TestCase
{
    private function definitionWithRequiredFields(array $fields): EntityDefinitionContract
    {
        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('fields')->willReturn($fields);
        return $def;
    }

    // -------------------------------------------------------------------------
    // 'update' context — absent field: required should be dropped/relaxed
    // -------------------------------------------------------------------------

    public function test_update_context_absent_field_does_not_have_required_rule(): void
    {
        $nameField  = TextField::make('name')->required();   // present in payload
        $emailField = TextField::make('email')->required();  // absent from payload

        $definition = $this->definitionWithRequiredFields([$nameField, $emailField]);

        // Only 'name' is present in the payload (presentKeys).
        $rules = (new RuleBuilder(locales: [], context: 'update'))
            ->build($definition, presentKeys: ['name']);

        // 'email' is absent from presentKeys → must NOT contain 'required'.
        if (isset($rules['email'])) {
            self::assertNotContains(
                'required',
                $rules['email'],
                "In 'update' context, 'email' is absent from payload — 'required' must be relaxed.",
            );
        } else {
            // Acceptable: field omitted entirely when no rules apply.
            $this->addToAssertionCount(1);
        }
    }

    public function test_update_context_absent_field_has_sometimes_rule_or_is_omitted(): void
    {
        $emailField = TextField::make('email')->required();
        $definition = $this->definitionWithRequiredFields([$emailField]);

        $rules = (new RuleBuilder(locales: [], context: 'update'))
            ->build($definition, presentKeys: []);

        // Either 'sometimes' is present OR the field is omitted entirely.
        $emailRules = $rules['email'] ?? [];
        $hasRequired  = in_array('required', $emailRules, true);

        self::assertFalse(
            $hasRequired,
            "In 'update' context with absent key, 'required' must not be present.",
        );
        // 'sometimes' or absence are both acceptable behaviors.
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // 'update' context — present field: required must be kept
    // -------------------------------------------------------------------------

    public function test_update_context_present_field_keeps_required_rule(): void
    {
        $nameField = TextField::make('name')->required();

        $definition = $this->definitionWithRequiredFields([$nameField]);

        $rules = (new RuleBuilder(locales: [], context: 'update'))
            ->build($definition, presentKeys: ['name']);

        self::assertArrayHasKey('name', $rules);
        self::assertContains(
            'required',
            $rules['name'],
            "In 'update' context, field present in payload must keep 'required'.",
        );
    }

    // -------------------------------------------------------------------------
    // 'create' context — unchanged behavior (all required fields emit required)
    // -------------------------------------------------------------------------

    public function test_create_context_always_emits_required_regardless_of_present_keys(): void
    {
        $nameField  = TextField::make('name')->required();
        $emailField = TextField::make('email')->required();

        $definition = $this->definitionWithRequiredFields([$nameField, $emailField]);

        // presentKeys contains only 'name', but 'create' context must not relax 'email'.
        $rules = (new RuleBuilder(locales: [], context: 'create'))
            ->build($definition, presentKeys: ['name']);

        self::assertArrayHasKey('email', $rules);
        self::assertContains(
            'required',
            $rules['email'],
            "In 'create' context, 'required' must always be emitted regardless of presentKeys.",
        );
    }

    public function test_default_context_behaves_like_create(): void
    {
        $emailField = TextField::make('email')->required();
        $definition = $this->definitionWithRequiredFields([$emailField]);

        // Default context (no $context arg) → behaves like 'create'.
        $rulesDefault = (new RuleBuilder())->build($definition);
        $rulesCreate  = (new RuleBuilder(locales: [], context: 'create'))->build($definition);

        self::assertSame($rulesCreate, $rulesDefault);
    }

    // -------------------------------------------------------------------------
    // build() with presentKeys in 'create' context does not break existing tests
    // -------------------------------------------------------------------------

    public function test_build_accepts_present_keys_without_breaking_create_semantics(): void
    {
        $nameField  = TextField::make('name')->required();
        $emailField = TextField::make('email')->required();

        $definition = $this->definitionWithRequiredFields([$nameField, $emailField]);

        // 'create' context with empty presentKeys — both fields keep 'required'.
        $rules = (new RuleBuilder(locales: [], context: 'create'))
            ->build($definition, presentKeys: []);

        self::assertContains('required', $rules['name']);
        self::assertContains('required', $rules['email']);
    }
}
