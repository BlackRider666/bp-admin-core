<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Events;

use BlackParadise\CoreAdmin\Domain\Contracts\Events\DomainEventContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

abstract class AbstractDomainEvent implements DomainEventContract
{
    public function __construct(
        private readonly EntityKey $entityKey,
        private readonly array $payload = [],
    ) {}

    public function getEntityKey(): EntityKey
    {
        return $this->entityKey;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
