<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;

/**
 * Embedded definition whose form-visible sub-fields include a CUSTOM field that
 * declares pointsBackTo()=true WITHOUT being a BelongsToField (its kind() is not
 * RelationKind::BelongsTo — it is not even a RelationFieldContract).
 *
 * Pins that BuildFormViewUseCase honours the FieldContract::pointsBackTo() predicate
 * for the A7 FK-skip — rather than an inlined `instanceof RelationFieldContract &&
 * kind()===BelongsTo && target()===host` check, which would MISS this field.
 *
 *   - keep            (TextField)                  — always present
 *   - custom_back_ref (custom, pointsBackTo()=true) — must be ABSENT post-filter
 */
final class StubEmbeddedDefinitionWithCustomBackRef implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'embedded_with_custom_back_ref';
    }

    public function label(): string
    {
        return 'Embedded With Custom Back Ref';
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
        return 'App\\Models\\CustomBackRefChild';
    }

    /** @return list<FieldContract> */
    public function fields(): array
    {
        return [
            TextField::make('keep'),
            new class ('custom_back_ref') extends AbstractField {
                public function type(): string
                {
                    return 'custom_back_ref';
                }

                public function pointsBackTo(string $hostModelClass): bool
                {
                    return true;
                }
            },
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
        return 'embedded_with_custom_back_ref';
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
