<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;

interface EntityDefinitionContract
{
    public function name(): string;
    public function label(): string;
    public function keyField(): string;
    public function keyType(): string;
    public function modelClass(): string;
    /**
     * @return array<FieldContract>
     */
    public function fields(): array;

    /**
     * @return array<ActionContract>
     */
    public function actions(): array;
    public function defaultPerPage(): int;

    /**
     * Field names to include in full-text search when Criteria::$search is set.
     * Return empty array to disable search for this entity.
     *
     * @return array<string>
     */
    public function searchFields(): array;
}
