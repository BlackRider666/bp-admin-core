<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
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
}
