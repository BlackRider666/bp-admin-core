<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

final class HasOneField extends AbstractRelationField
{
    private ?string $foreignKey = null;

    public static function make(string $name, string $target): self
    {
        return new self($name, 'hasOne', $target);
    }

    public function type(): string
    {
        return 'has_one';
    }

    public function withForeignKey(string $column): self
    {
        $this->foreignKey = $column;
        return $this;
    }

    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }
}
