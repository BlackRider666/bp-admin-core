<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;

final class TextareaField extends AbstractField
{
    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'textarea';
    }
}
