<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use stdClass;

/**
 * Embedded definition stub used as the target for ->embed() calls in
 * ResolveEmbeddedRelationsUseCaseTest.
 *
 * The use case never instantiates this class via "new $class()" — the
 * resolveDefinition closure stub does the lookup, so we only need a
 * concrete EntityDefinitionContract subclass for type assertions.
 */
final class StubEmbeddedDefinition implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'embedded';
    }
    public function label(): string
    {
        return 'Embedded';
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
        return [];
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
