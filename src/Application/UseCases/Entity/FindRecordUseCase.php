<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

final readonly class FindRecordUseCase
{
    public function __construct(
        private EntityRepositoryInterface $entityRepository,
        private AuthorizationProviderContract $authorizationProvider,
        private EntityDefinitionContract $entityDefinition,
    ) {}

    /**
     * @throws UnauthorizedException
     * @throws EntityNotFoundException
     */
    public function execute(EntityKey $key): EntityRecordContract
    {
        if (!$this->authorizationProvider->can('view', $this->entityDefinition)) {
            throw new UnauthorizedException($this->entityDefinition->name(), 'view');
        }

        $record = $this->entityRepository->find($this->entityDefinition, $key);
        if (!$record instanceof EntityRecordContract) {
            throw new EntityNotFoundException($this->entityDefinition->name(), $key->value);
        }

        return $record;
    }
}
