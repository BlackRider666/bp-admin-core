<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Exceptions;

use DomainException;

class EntityNotFoundException extends DomainException
{
    public function __construct(string $entity, mixed $key)
    {
        parent::__construct("Entity [{$entity}] with key [{$key}] not found.");
    }
}
