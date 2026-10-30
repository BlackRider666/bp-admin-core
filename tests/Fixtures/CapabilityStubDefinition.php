<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use stdClass;

/**
 * Minimal definition with configurable capabilities (Task 4.1 / A29, A30, A39).
 * Declares the target-contract methods up-front; compiles against the current contract.
 */
final readonly class CapabilityStubDefinition implements EntityDefinitionContract
{
    /** @param list<class-string<ActionContract>> $actions */
    public function __construct(
        private EntityCapabilities $capabilities = new EntityCapabilities(),
        private array $actions = [],
    ) {}

    public function name(): string
    {
        return 'capped';
    }

    public function label(): string
    {
        return 'Capped';
    }

    public function keyField(): string
    {
        return 'id';
    }

    public function keyType(): string
    {
        return 'int';
    }

    public function modelClass(): string
    {
        return stdClass::class;
    }

    public function fields(): array
    {
        return [];
    }

    public function actions(): array
    {
        return $this->actions;
    }

    public function defaultPerPage(): int
    {
        return 25;
    }

    public function searchFields(): array
    {
        return [];
    }

    public function resolveName(): string
    {
        return 'capped';
    }

    public function capabilities(): EntityCapabilities
    {
        return $this->capabilities;
    }

    /**
     * @return array<mixed>
     */
    public function filters(): array
    {
        return [];
    }

    public function repository(): null
    {
        return null;
    }

    public function mutator(): null
    {
        return null;
    }
}
