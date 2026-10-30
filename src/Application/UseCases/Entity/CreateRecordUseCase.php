<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Events\EntityCreated;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use LogicException;

final readonly class CreateRecordUseCase
{
    /**
     * @param bool $enforceCapability When false the standalone {@see Capability::Create} gate is skipped.
     *                                Reserved for embedded-child creation: that capability governs the
     *                                standalone create surface only, so embed-only (non-creatable)
     *                                definitions can still be created as children of a host.
     *                                Authorization is enforced regardless.
     */
    public function __construct(
        private EntityMutatorInterface $mutator,
        private AuthorizationProviderContract $authorizationProvider,
        private EntityDefinitionContract $entityDefinition,
        private ValidationProviderContract $validator,
        private EventDispatcherContract $dispatcher,
        private LocaleProviderContract $localeProvider,
        private bool $enforceCapability = true,
    ) {}

    /**
     * @throws UnauthorizedException
     * @throws CapabilityDeniedException
     * @throws ValidationException
     */
    public function execute(EntityRecordContract $record): EntityRecordContract
    {
        if (!$this->authorizationProvider->can('create', $this->entityDefinition)) {
            throw new UnauthorizedException($this->entityDefinition->name(), 'create');
        }

        if ($this->enforceCapability && !$this->entityDefinition->capabilities()->allows(Capability::Create)) {
            throw new CapabilityDeniedException($this->entityDefinition->name(), Capability::Create);
        }

        $this->validator->validate(
            $record->attributes(),
            (new RuleBuilder($this->localeProvider->availableLocales(), 'create'))
                ->build($this->entityDefinition, array_keys($record->attributes())),
        );

        $created = $this->mutator->create($record);

        if ($created->id() === null) {
            throw new LogicException(
                'Mutator returned record without id — persistence invariant violated.',
            );
        }

        $this->dispatcher->dispatch(new EntityCreated(
            new EntityKey($created->id(), $this->entityDefinition->keyType()),
            $created->attributes(),
        ));

        return $created;
    }
}
