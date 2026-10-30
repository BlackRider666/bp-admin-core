<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Persistence;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

interface OwnedDeletesCollectorContract
{
    /**
     * Collects owned (cascade-on-delete) child records of a host record.
     *
     * @return array<int, array{def: EntityDefinitionContract, key: EntityKey}>
     */
    public function collect(EntityDefinitionContract $definition, EntityRecordContract $host): array;
}
