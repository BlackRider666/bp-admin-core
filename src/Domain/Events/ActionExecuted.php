<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Events;

use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\DomainEventContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

/** Fired after a successfully committed action. */
final readonly class ActionExecuted implements DomainEventContract
{
    /**
     * Sentinel returned by {@see getEntityKey()} for entity-scope actions that
     * target no record (DomainEventContract::getEntityKey() is non-nullable).
     */
    public const ENTITY_SCOPE_KEY = '*';

    /** @param list<EntityKey> $keys */
    public function __construct(
        public string $entity,
        public string $action,
        public ActionScope $scope,
        public array $keys,
        public ActionResult $result,
    ) {}

    /** First affected key, or the {@see self::ENTITY_SCOPE_KEY} sentinel when there are none. */
    public function getEntityKey(): EntityKey
    {
        return $this->keys[0] ?? new EntityKey(self::ENTITY_SCOPE_KEY, 'string');
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return [
            'entity' => $this->entity,
            'action' => $this->action,
            'scope' => $this->scope->value,
            'keys' => array_map(static fn(EntityKey $k): int|string => $k->value, $this->keys),
            'outcome' => $this->result->outcome->value,
        ];
    }
}
