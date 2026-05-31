<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;

/**
 * In-memory test double for {@see AuthorizationProviderContract}.
 *
 * Default behavior: deny everything. Configure allowed (entity, action) pairs
 * via allow() before exercising the use case, or call allowAll() to permit all.
 *
 *     $auth = (new InMemoryAuthorizationProvider())->allow('users', 'create');
 *     $auth->can('create', $usersDefinition); // true
 */
final class InMemoryAuthorizationProvider implements AuthorizationProviderContract
{
    /** @var array<string, array<string, true>> */
    private array $allowed = [];

    private bool $allowAll = false;

    public function allow(string $entityName, string $action): self
    {
        $this->allowed[$entityName][$action] = true;
        return $this;
    }

    public function allowAll(): self
    {
        $this->allowAll = true;
        return $this;
    }

    public function denyAll(): self
    {
        $this->allowAll = false;
        $this->allowed  = [];
        return $this;
    }

    public function can(string $action, EntityDefinitionContract $entityDefinition): bool
    {
        if ($this->allowAll) {
            return true;
        }

        return isset($this->allowed[$entityDefinition->name()][$action]);
    }
}
