<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Auth;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;

interface AuthorizationProviderContract
{
    public function can(string $action, EntityDefinitionContract $entityDefinition): bool;
}
