<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\RuleBuilder;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;

final class RuleBuilderTest extends TestCase
{
    public function test_from_definition_returns_rules_keyed_by_field_name(): void
    {
        $field = TextField::make('email')->withRules(['required', 'email']);

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame(['email' => ['required', 'email']], $rules);
    }

    public function test_from_definition_skips_fields_with_empty_rules(): void
    {
        $fieldWithRules = TextField::make('email')->withRules(['required', 'email']);
        $fieldWithoutRules = TextField::make('notes');

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$fieldWithRules, $fieldWithoutRules]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertArrayHasKey('email', $rules);
        self::assertArrayNotHasKey('notes', $rules);
    }

    public function test_from_definition_returns_empty_array_when_no_fields(): void
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame([], $rules);
    }

    public function test_from_definition_returns_empty_array_when_all_fields_have_empty_rules(): void
    {
        $field1 = TextField::make('name');
        $field2 = TextField::make('bio');

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$field1, $field2]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame([], $rules);
    }

    public function test_from_definition_returns_rules_for_multiple_fields(): void
    {
        $nameField = TextField::make('name')->withRules(['required', 'max:100']);
        $emailField = TextField::make('email')->withRules(['required', 'email']);
        $notesField = TextField::make('notes'); // no rules — should be skipped

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$nameField, $emailField, $notesField]);

        $rules = RuleBuilder::fromDefinition($definition);

        self::assertSame([
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email'],
        ], $rules);
    }
}
