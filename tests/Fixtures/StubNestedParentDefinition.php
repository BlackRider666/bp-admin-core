<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;

/**
 * Level-1 embedded definition for 2-level nested-embed host-threading tests
 * (BuildFormViewNestedEmbedHostThreadingTest).
 *
 * modelClass = 'App\Models\Parent'
 * Fields:
 *   - BelongsToField 'grandparent_id' → 'App\Models\Grandparent'   ← back-FK to top host
 *   - TextField 'title'                                              ← plain scalar, must survive
 *   - HasManyField 'children' → embed(StubNestedChildDefinition)    ← level-2 container
 *
 * When this definition is processed by BuildFormViewUseCase with
 * hostModelClass = 'App\Models\Grandparent', the 'grandparent_id' field must be
 * excluded (pointsBackTo returns true) while 'title' and 'children' survive.
 */
final class StubNestedParentDefinition implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'parents';
    }

    public function label(): string
    {
        return 'Parents';
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
        return 'App\\Models\\Parent';
    }

    /** @return list<FieldContract> */
    public function fields(): array
    {
        return [
            BelongsToField::make('grandparent_id', 'App\\Models\\Grandparent'),
            TextField::make('title'),
            HasManyField::make('children', 'App\\Models\\Child')
                ->embed(StubNestedChildDefinition::class),
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
        return 'parents';
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
