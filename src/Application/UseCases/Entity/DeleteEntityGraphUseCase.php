<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\OwnedDeletesCollectorContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\WriteScopeContract;
use BlackParadise\CoreAdmin\Domain\Events\EntityDeleted;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

/**
 * Deletes a host record and its owned children (as decided by the collector:
 * none for a soft delete, the owned children otherwise) in one write scope.
 * Events are dispatched through a scope-aware dispatcher, i.e. after commit.
 */
final readonly class DeleteEntityGraphUseCase
{
    public function __construct(
        private AuthorizationProviderContract $authorizationProvider,
        private WriteScopeContract $writeScope,
        private EntityRepositoryInterface $repository,
        private EntityMutatorInterface $mutator,
        private OwnedDeletesCollectorContract $ownedCollector,
        private EventDispatcherContract $dispatcher,
    ) {}

    /**
     * @throws UnauthorizedException
     * @throws CapabilityDeniedException
     * @throws EntityNotFoundException
     */
    public function execute(EntityDefinitionContract $definition, EntityKey $key): bool
    {
        if (!$this->authorizationProvider->can('delete', $definition)) {
            throw new UnauthorizedException($definition->name(), 'delete');
        }

        if (!$definition->capabilities()->allows(Capability::Delete)) {
            throw new CapabilityDeniedException($definition->name(), Capability::Delete);
        }

        $host = $this->repository->find($definition, $key);
        if (!$host instanceof EntityRecordContract) {
            throw new EntityNotFoundException($definition->name(), $key->value);
        }

        $this->writeScope->run(function () use ($definition, $key, $host): void {
            $owned = $this->ownedCollector->collect($definition, $host);

            foreach ($owned as $child) {
                if (!$this->authorizationProvider->can('delete', $child['def'])) {
                    throw new UnauthorizedException($child['def']->name(), 'delete');
                }
            }

            foreach ($owned as $child) {
                if ($this->mutator->delete($child['key'], $child['def'])) {
                    $this->dispatcher->dispatch(new EntityDeleted($child['key']));
                }
            }

            if (!$this->mutator->delete($key, $definition)) {
                throw new EntityNotFoundException($definition->name(), $key->value);
            }
            $this->dispatcher->dispatch(new EntityDeleted($key));
        });

        return true;
    }
}
