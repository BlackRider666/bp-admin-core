<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use stdClass;

final class StubEntityDefinition implements EntityDefinitionContract
{
    public function name(): string
    {
        return 'stub';
    }

    public function label(): string
    {
        return 'Stub';
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

    public function resolveName(): string
    {
        return 'stub';
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
