<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Exceptions;

use DomainException;

final class ActionNotFoundException extends DomainException
{
    public function __construct(string $entity, string $action)
    {
        parent::__construct("Action [{$action}] not found on entity [{$entity}].");
    }
}
