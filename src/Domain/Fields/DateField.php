<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;

final class DateField extends AbstractField
{
    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'date';
    }

    protected function typeRules(): array
    {
        // Nullable first so optional dates accept an empty submit; AbstractField
        // auto-suppresses it when the field is marked required().
        return [Rule::Nullable, Rule::Date];
    }
}
