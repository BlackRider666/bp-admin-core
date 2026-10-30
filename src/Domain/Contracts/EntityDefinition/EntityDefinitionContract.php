<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;

interface EntityDefinitionContract
{
    public function name(): string;
    public function label(): string;
    public function keyField(): string;
    public function keyType(): string;
    /**
     * Eloquent/model class, or null for a model-less definition.
     */
    public function modelClass(): ?string;
    /**
     * @return array<FieldContract>
     */
    public function fields(): array;

    /**
     * @return list<class-string<ActionContract>>
     */
    public function actions(): array;
    public function defaultPerPage(): int;

    public function capabilities(): EntityCapabilities;

    /**
     * Instance method (not static) to keep BC with definitions overriding it.
     */
    public function resolveName(): string;

    /**
     * @return array<mixed>
     */
    public function filters(): array;

    /**
     * Class-string of a custom repository, or null for the globally bound one.
     */
    public function repository(): ?string;

    /**
     * Class-string of a custom mutator, or null for the globally bound one.
     */
    public function mutator(): ?string;

    /**
     * Field names to include in full-text search when Criteria::$search is set.
     * Return empty array to disable search for this entity.
     *
     * @return array<string>
     */
    public function searchFields(): array;
}
