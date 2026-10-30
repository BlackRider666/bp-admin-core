<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Events\EntityUpdated;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use LogicException;

final readonly class UpdateRecordUseCase
{
    /**
     * @param bool $enforceCapability When false the standalone {@see Capability::Update} gate is skipped.
     *                                Reserved for embedded-child updates: capabilities govern the
     *                                standalone surface only. Authorization is enforced regardless.
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
     * @throws EntityNotFoundException
     */
    public function execute(EntityKey $key, EntityRecordContract $record): EntityRecordContract
    {
        if (!$this->authorizationProvider->can('update', $this->entityDefinition)) {
            throw new UnauthorizedException($this->entityDefinition->name(), 'update');
        }

        if ($this->enforceCapability && !$this->entityDefinition->capabilities()->allows(Capability::Update)) {
            throw new CapabilityDeniedException($this->entityDefinition->name(), Capability::Update);
        }

        $this->validator->validate(
            $record->attributes(),
            (new RuleBuilder($this->localeProvider->availableLocales(), 'update'))
                ->build($this->entityDefinition, array_keys($record->attributes())),
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
