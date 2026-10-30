<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;

/**
 * Level-2 embedded definition for 2-level nested-embed host-threading tests
 * (BuildFormViewNestedEmbedHostThreadingTest).
 *
 * modelClass = 'App\Models\Child'
 * Fields:
 *   - BelongsToField 'parent_id'      → 'App\Models\Parent'      ← back-FK to level-1 host
 *   - BelongsToField 'grandparent_id' → 'App\Models\Grandparent' ← NOT a back-FK at level-2
 *   - TextField 'name'                                            ← plain scalar, must survive
 *
 * When this definition is processed by BuildFormViewUseCase with
 * hostModelClass = 'App\Models\Parent' (the level-1 def's own modelClass),
 * 'parent_id' must be excluded while 'grandparent_id' and 'name' survive.
 * If the implementation incorrectly left the host at 'App\Models\Grandparent',
 * 'grandparent_id' would be wrongly excluded and the threading test would catch it.
 */
final class StubNestedChildDefinition implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'children';
    }

    public function label(): string
    {
        return 'Children';
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
        return 'App\\Models\\Child';
    }

    /** @return list<FieldContract> */
    public function fields(): array
    {
        return [
            BelongsToField::make('parent_id', 'App\\Models\\Parent'),
            BelongsToField::make('grandparent_id', 'App\\Models\\Grandparent'),
            TextField::make('name'),
        ];
    }

    /** @return list<class-string<ActionContract>> */
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

    public function resolveName(): string
    {
        return 'children';
    }

    public function capabilities(): EntityCapabilities
    {
        return EntityCapabilities::all();
    }

    /**
     * @return array<mixed>
     */
    public function filters(): array
    {
        return [];
    }

    public function repository(): null
    {
        return null;
    }

    public function mutator(): null
    {
        return null;
    }
}
