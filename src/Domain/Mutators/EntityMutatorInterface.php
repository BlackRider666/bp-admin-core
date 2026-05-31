<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Mutators;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

interface EntityMutatorInterface
{
    public function create(EntityRecordContract $record): EntityRecordContract;

    public function update(EntityKey $key, EntityRecordContract $record): EntityRecordContract;

    public function delete(EntityKey $key, EntityDefinitionContract $entityDefinition): bool;
}
