<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use stdClass;

/**
 * An EntityDefinitionContract that embeds itself via a BelongsToField.
 *
 * Used to verify that BuildFormViewUseCase guards against cyclic embed
 * configurations (self-reference or mutual reference A↔B) without stack
 * overflow. The embed() call is deferred to fields() so the class can
 * be defined before PHP resolves its own FQCN constant.
 */
final class StubSelfReferencingEmbeddedDefinition implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'self_referencing';
    }

    public function label(): string
    {
        return 'Self Referencing';
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
            // This field embeds the same definition class — creating a cycle.
            BelongsToField::make('parent_id', stdClass::class)
                ->embed(self::class),
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
