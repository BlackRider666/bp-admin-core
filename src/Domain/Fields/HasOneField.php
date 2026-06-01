<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

final class HasOneField extends AbstractRelationField
{
    public static function make(string $name, string $target): self
    {
        return new self($name, 'hasOne', $target);
    }

    public function type(): string
    {
        return 'has_one';
    }
}
