<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Commands;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;

final readonly class SaveEntityGraphCommand
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        public EntityDefinitionContract $definition,
        public ?EntityKey $key = null,
        public array $attributes = [],
    ) {}
}
