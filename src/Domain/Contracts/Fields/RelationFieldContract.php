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
}
