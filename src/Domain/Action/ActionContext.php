<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Action;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use InvalidArgumentException;

final readonly class ActionContext
{
    /**
     * @param list<EntityKey> $keys
     * @param array<string, mixed> $input
     */
    public function __construct(
        public EntityDefinitionContract $definition,
        public ActionScope $scope,
        public array $keys = [],
        public array $input = [],
    ) {
        foreach ($keys as $key) {
            // @phpstan-ignore instanceof.alwaysTrue (runtime guard for untyped callers)
            if (!$key instanceof EntityKey) {
                throw new InvalidArgumentException('ActionContext keys must be EntityKey instances.');
            }
        }
    }
}
