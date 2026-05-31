<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Exceptions;

use DomainException;

final class UnauthorizedException extends DomainException
{
    public function __construct(string $entity, string $action)
    {
        parent::__construct('You can\'t ' . $action . ' ' . $entity, 403);
    }
}
