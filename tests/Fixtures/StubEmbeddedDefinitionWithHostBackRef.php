<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;

/**
 * Embedded definition stub that contains a belongsTo pointing back at the host
 * model class and a belongsTo pointing at a different model.
 *
 * Used to pin A7: BuildFormViewUseCase must filter sub-fields whose
 * pointsBackTo($hostModelClass) returns true OUT of meta['embeddedFields'].
 *
 * Fields:
 *   - title        (TextField)            — always present
 *   - post_id      (BelongsTo Post)       — back-FK to host; must be ABSENT post-filter
 *   - category_id  (BelongsTo Category)   — FK to a different model; must REMAIN
 *
 * Tests must stub entityDefinition->modelClass() to 'App\Models\Post' so that
 * BelongsToField('post_id', 'App\Models\Post')->pointsBackTo('App\Models\Post') === true.
 */
final class StubEmbeddedDefinitionWithHostBackRef implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'embedded_with_host_back_ref';
    }

    public function label(): string
    {
        return 'Embedded With Host Back Ref';
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
        return 'App\\Models\\PostAddress';
    }

    /** @return list<FieldContract> */
    public function fields(): array
    {
        return [
            TextField::make('title'),
            BelongsToField::make('post_id', 'App\\Models\\Post'),
            BelongsToField::make('category_id', 'App\\Models\\Category'),
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
