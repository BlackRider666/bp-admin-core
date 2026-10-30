<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Events\EntityDeleted;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

final readonly class DeleteRecordUseCase
{
    public function __construct(
        // Kept for ctor BC; existence is decided by the mutator result (see rector.php skip).
        private EntityRepositoryInterface $repository,
        private EntityMutatorInterface $mutator,
        private AuthorizationProviderContract $authorizationProvider,
        private EntityDefinitionContract $entityDefinition,
        private EventDispatcherContract $dispatcher,
    ) {}

    /**
     * @throws UnauthorizedException
     * @throws CapabilityDeniedException
     * @throws EntityNotFoundException
     */
    public function execute(EntityKey $key): bool
    {
        if (!$this->authorizationProvider->can('delete', $this->entityDefinition)) {
            throw new UnauthorizedException($this->entityDefinition->name(), 'delete');
        }

        if (!$this->entityDefinition->capabilities()->allows(Capability::Delete)) {
            throw new CapabilityDeniedException($this->entityDefinition->name(), Capability::Delete);
        }

        if (!$this->mutator->delete($key, $this->entityDefinition)) {
            throw new EntityNotFoundException($this->entityDefinition->name(), $key->value);
        }

        $this->dispatcher->dispatch(new EntityDeleted($key));

        return true;
    }
}
