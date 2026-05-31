<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

/**
 * In-memory test double for {@see EntityMutatorInterface}.
 *
 * - create() assigns a fresh auto-increment id and returns a new EntityRecord
 *   with the id set on its key field.
 * - update() returns a new EntityRecord with the given $key assigned to the
 *   key field.
 * - delete() always succeeds (returns true).
 *
 * Counters are exposed for assertions:
 *
 *     $mutator = new InMemoryEntityMutator();
 *     $useCase->execute(...);
 *     $this->assertSame(1, $mutator->createCalls);
 */
final class InMemoryEntityMutator implements EntityMutatorInterface
{
    public int $createCalls = 0;
    public int $updateCalls = 0;
    public int $deleteCalls = 0;

    private int $nextId = 1;

    public function create(EntityRecordContract $record): EntityRecordContract
    {
        ++$this->createCalls;

        $definition = $record->definition();
        $attrs      = $record->attributes();
        $attrs[$definition->keyField()] = $this->nextId++;

        return new EntityRecord($definition, $attrs);
    }

    public function update(EntityKey $key, EntityRecordContract $record): EntityRecordContract
    {
        ++$this->updateCalls;

        $definition = $record->definition();
        $attrs      = $record->attributes();
        $attrs[$definition->keyField()] = $key->value;

        return new EntityRecord($definition, $attrs);
    }

    public function delete(EntityKey $key, EntityDefinitionContract $entityDefinition): bool
    {
        ++$this->deleteCalls;
        return true;
    }
}
