<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Events;

use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

interface DomainEventContract
{
    public function getEntityKey(): EntityKey;
    public function getPayload(): array;
}
