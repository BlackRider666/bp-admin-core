<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use stdClass;

/**
 * Part of a mutual cyclic embed pair: A embeds B, B embeds A.
 *
 * Used to verify BuildFormViewUseCase guards against infinite recursion in
 * multi-step cycles (A → B → A → …) without a stack overflow.
 *
 * @see StubCyclicEmbedDefinitionA
 */
final class StubCyclicEmbedDefinitionB implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'cyclic_b';
    }

    public function label(): string
    {
        return 'Cyclic B';
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
            TextField::make('b_name'),
            BelongsToField::make('a_id', stdClass::class)
                ->embed(StubCyclicEmbedDefinitionA::class),
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
