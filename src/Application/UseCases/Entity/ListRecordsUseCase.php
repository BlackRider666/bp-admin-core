<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Query\Criteria;
use BlackParadise\CoreAdmin\Domain\Query\PaginatedResult;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;

final readonly class ListRecordsUseCase
{
    public function __construct(
        private EntityRepositoryInterface $entityRepository,
        private AuthorizationProviderContract $authorizationProvider,
        private EntityDefinitionContract $entityDefinition,
    ) {}

    /**
     * @throws UnauthorizedException
     */
    public function execute(Criteria $criteria): PaginatedResult
    {
        if (!$this->authorizationProvider->can('list', $this->entityDefinition)) {
            throw new UnauthorizedException($this->entityDefinition->name(), 'list');
        }

        return $this->entityRepository->list($this->entityDefinition, $criteria);
    }
}
