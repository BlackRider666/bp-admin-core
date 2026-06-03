<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use stdClass;

/**
 * Embedded definition stub that carries relation sub-fields (belongsTo,
 * belongsToMany) so that BuildFormViewUseCase can be tested for recursive
 * option decoration inside embedded fieldsets.
 */
final class StubEmbeddedDefinitionWithRelations implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'embedded_with_relations';
    }

    public function label(): string
    {
        return 'Embedded With Relations';
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
        return stdClass::class;
    }

    public function fields(): array
    {
        return [
            TextField::make('name'),
            BelongsToField::make('city_id', 'App\\Models\\City'),
            BelongsToManyField::make('tags', 'App\\Models\\Tag'),
        ];
    }

    public function actions(): array
    {
        return [];
    }

    public function defaultPerPage(): int
    {
        return 25;
    }

    public function searchFields(): array
    {
        return [];
    }
}
