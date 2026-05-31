<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

final class BelongsToField extends AbstractRelationField
{
    public static function make(string $name, string $target): self
    {
        return new self($name, 'belongsTo', $target);
    }

    public function type(): string
    {
        return 'belongs_to';
    }
}
