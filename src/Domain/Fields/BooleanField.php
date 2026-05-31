<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;

final class BooleanField extends AbstractField
{
    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'boolean';
    }
}
