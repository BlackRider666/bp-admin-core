<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Fields;

interface RelationFieldContract extends FieldContract
{
    public function relationKind(): string;
    public function target(): string;
    public function multiple(): bool;
    public function createInline(): bool;
    public function relationName(): string;
    public function displayField(): string;
    public function isEmbedded(): bool;
    public function isOwned(): bool;
    public function embeddedDefinition(): ?string;
    /** @return array<string, mixed> */
    public function state(): array;

    /**
     * Returns the constraints that the RelationOptionsProvider MUST apply when
     * fetching selectable options for this field.
     *
     * Each entry is `['column' => string, 'value' => mixed]`.
     * An empty array means "no restrictions — return all rows".
     *
     * The provider (infrastructure) reads these and applies them as WHERE
     * clauses (Eloquent), query params, or any equivalent filtering mechanism.
     * The domain does NOT know how filtering is implemented.
     *
     * @return list<array{column: string, value: mixed}>
     */
    public function optionConstraints(): array;
}
