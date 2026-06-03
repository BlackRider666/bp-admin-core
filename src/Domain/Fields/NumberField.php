<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;

final class NumberField extends AbstractField
{
    private bool $integerOnly = false;

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'number';
    }

    /**
     * Restrict to integer values only. Emits `integer` instead of `numeric`.
     */
    public function integer(): self
    {
        $this->integerOnly = true;

        return $this;
    }

    public function isInteger(): bool
    {
        return $this->integerOnly;
    }

    /**
     * Emits `integer` (when {@see integer()} was called) or `numeric` by default.
     *
     * {@inheritdoc}
     */
    protected function typeRules(): array
    {
        return [$this->integerOnly ? Rule::Integer : Rule::Numeric];
    }
}
