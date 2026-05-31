<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Events\EntityUpdated;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use LogicException;

final readonly class UpdateRecordUseCase
{
    public function __construct(
        private EntityRepositoryInterface $repository,
        private EntityMutatorInterface $mutator,
        private AuthorizationProviderContract $authorizationProvider,
        private EntityDefinitionContract $entityDefinition,
        private ValidationProviderContract $validator,
        private EventDispatcherContract $dispatcher,
    ) {}

    /**
     * @throws UnauthorizedException
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function execute(EntityKey $key, EntityRecordContract $record): EntityRecordContract
    {
        if (!$this->authorizationProvider->can('update', $this->entityDefinition)) {
            throw new UnauthorizedException($this->entityDefinition->name(), 'update');
        }

        if (!$this->repository->exists($this->entityDefinition, $key)) {
            throw new EntityNotFoundException($this->entityDefinition->name(), $key->value);
        }

        $this->validator->validate(
            $record->attributes(),
            RuleBuilder::fromDefinition($this->entityDefinition),
        );

        $updated = $this->mutator->update($key, $record);

        if ($updated->id() === null) {
            throw new LogicException(
                'Mutator returned record without id — persistence invariant violated.',
            );
        }

        $this->dispatcher->dispatch(new EntityUpdated($key, $updated->attributes()));

        return $updated;
    }
}
