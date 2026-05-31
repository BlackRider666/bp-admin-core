<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

final class MorphToField extends AbstractRelationField
{
    public static function make(string $name, string $target): self
    {
        return new self($name, 'morphTo', $target);
    }

    public function type(): string
    {
        return 'morph_to';
    }
}
