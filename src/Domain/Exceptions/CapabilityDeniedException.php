<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Exceptions;

use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use DomainException;

final class CapabilityDeniedException extends DomainException
{
    public function __construct(string $entity, Capability $capability)
    {
        parent::__construct("Entity [{$entity}] does not allow [{$capability->value}].");
    }
}
