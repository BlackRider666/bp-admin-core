<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Query\Criteria;
use BlackParadise\CoreAdmin\Domain\Query\PaginatedResult;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use InvalidArgumentException;

/**
 * In-memory test double for {@see EntityRepositoryInterface}.
 *
 * Records are bucketed by EntityDefinition name. Seed via seed() before exercising
 * a use case. list() returns all seeded items as a single page — does not interpret
 * Criteria filters/sort/search/pagination. Tests should assert at the use-case
 * boundary (e.g. that the use case constructed the right Criteria), not on filter
 * semantics here.
 */
final class InMemoryEntityRepository implements EntityRepositoryInterface
{
    /** @var array<string, array<string, EntityRecordContract>> */
    private array $store = [];

    public function seed(EntityDefinitionContract $definition, EntityRecordContract $record): self
    {
        $id = $record->id();

        if (!is_int($id) && !is_string($id)) {
            throw new InvalidArgumentException(
                'InMemoryEntityRepository::seed() requires the record to have a non-null int|string id.',
            );
        }

        $this->store[$definition->name()][(string) $id] = $record;
        return $this;
    }

    public function list(EntityDefinitionContract $entityDefinition, Criteria $criteria): PaginatedResult
    {
        $items = array_values($this->store[$entityDefinition->name()] ?? []);

        return new PaginatedResult(
            items: $items,
            total: count($items),
            page: $criteria->page,
            perPage: $criteria->perPage,
        );
    }

    public function find(EntityDefinitionContract $entityDefinition, EntityKey $key): ?EntityRecordContract
    {
        return $this->store[$entityDefinition->name()][(string) $key->value] ?? null;
    }

    public function exists(EntityDefinitionContract $entityDefinition, EntityKey $key): bool
    {
        return isset($this->store[$entityDefinition->name()][(string) $key->value]);
    }
}
