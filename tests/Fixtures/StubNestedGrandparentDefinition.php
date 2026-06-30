<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;

/**
 * Top-level grandparent definition for 2-level nested-embed host-threading tests
 * (BuildFormViewNestedEmbedHostThreadingTest).
 *
 * modelClass = 'App\Models\Grandparent'
 * Fields:
 *   - HasManyField 'parents' → embed(StubNestedParentDefinition)
 *
 * embed() does class_exists() + is_subclass_of(EntityDefinitionContract), so
 * this must be a real named class, not an anonymous class.
 */
final class StubNestedGrandparentDefinition implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'grandparents';
    }

    public function label(): string
    {
        return 'Grandparents';
    }

    public function keyField(): string
    {
        return 'id';
    }

    public function keyType(): string
    {
        return 'int';
    }

    public function modelClass(): string
    {
        return 'App\\Models\\Grandparent';
    }

    /** @return list<FieldContract> */
    public function fields(): array
    {
        return [
            HasManyField::make('parents', 'App\\Models\\Parent')
                ->embed(StubNestedParentDefinition::class),
        ];
    }

    /** @return list<ActionContract> */
    public function actions(): array
    {
        return [];
    }

    public function defaultPerPage(): int
    {
        return 25;
    }

    /** @return list<string> */
    public function searchFields(): array
    {
        return [];
    }
}
