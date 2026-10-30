<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Commands\SaveEntityGraphCommand;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\EmbeddedChildWriterContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\WriteScopeContract;
use BlackParadise\CoreAdmin\Domain\Contracts\UseCases\SaveEntityGraphUseCaseContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use Closure;

/**
 * Saves a host record together with its embedded children atomically:
 * authorization first, then ONE write scope for resolve + host save + children.
 * Any failure rolls the scope back (no rows, no events).
 */
final readonly class SaveEntityGraphUseCase implements SaveEntityGraphUseCaseContract
{
    /**
     * @param Closure(EntityDefinitionContract): CreateRecordUseCase $createRecord
     * @param Closure(EntityDefinitionContract): UpdateRecordUseCase $updateRecord
     */
    public function __construct(
        private AuthorizationProviderContract $authorizationProvider,
        private WriteScopeContract $writeScope,
        private EntityRepositoryInterface $repository,
        private ResolveEmbeddedRelationsUseCase $embeddedResolver,
        private EmbeddedChildWriterContract $embeddedWriter,
        private Closure $createRecord,
        private Closure $updateRecord,
    ) {}

    /**
     * @throws UnauthorizedException
     * @throws CapabilityDeniedException
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function execute(SaveEntityGraphCommand $command): EntityRecordContract
    {
        $definition = $command->definition;
        $ability = $command->key instanceof EntityKey ? 'update' : 'create';

        if (!$this->authorizationProvider->can($ability, $definition)) {
            throw new UnauthorizedException($definition->name(), $ability);
        }

        $capability = $command->key instanceof EntityKey ? Capability::Update : Capability::Create;
        if (!$definition->capabilities()->allows($capability)) {
            throw new CapabilityDeniedException($definition->name(), $capability);
        }

        /** @var EntityRecordContract $saved */
        $saved = $this->writeScope->run(function () use ($command, $definition): EntityRecordContract {
            $key = $command->key;

            if (!$key instanceof EntityKey) {
                $resolved = $this->embeddedResolver->resolveOnStore($definition, $command->attributes);
                $host = ($this->createRecord)($definition)->execute(new EntityRecord($definition, $resolved['attributes']));
            } else {
                $current = $this->repository->find($definition, $key);
                if (!$current instanceof EntityRecordContract) {
                    throw new EntityNotFoundException($definition->name(), $key->value);
                }
                $resolved = $this->embeddedResolver->resolveOnUpdate($definition, $current, $command->attributes);
                $host = ($this->updateRecord)($definition)->execute($key, new EntityRecord($definition, $resolved['attributes']));
            }

            $this->embeddedWriter->writeAll($definition, $host, $resolved['defer']);

            return $host;
        });

        return $saved;
    }
}
