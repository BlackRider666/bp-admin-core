<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Repositories;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Query\Criteria;
use BlackParadise\CoreAdmin\Domain\Query\PaginatedResult;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

interface EntityRepositoryInterface
{
    public function list(EntityDefinitionContract $entityDefinition, Criteria $criteria): PaginatedResult;
    public function find(EntityDefinitionContract $entityDefinition, EntityKey $key): ?EntityRecordContract;
    public function exists(EntityDefinitionContract $entityDefinition, EntityKey $key): bool;
}
