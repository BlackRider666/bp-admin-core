<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;

final class HiddenField extends AbstractField
{
    public function __construct(
        string $name,
        ?string $label = null,
        array $rules = [],
    ) {
        parent::__construct(
            name: $name,
            label: $label,
            rules: $rules,
            visibleOnList: false,
            visibleOnForm: true,
            visibleOnShow: false,
        );
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'hidden';
    }
}
