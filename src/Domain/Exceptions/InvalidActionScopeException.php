<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Exceptions;

use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use DomainException;

final class InvalidActionScopeException extends DomainException
{
    public function __construct(string $action, ActionScope $scope)
    {
        parent::__construct("Action [{$action}] does not support scope [{$scope->value}].");
    }
}
