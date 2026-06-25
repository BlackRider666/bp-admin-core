<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;

final class NumberField extends AbstractField
{
    private bool $integerOnly = false;

    private int|float|null $minValue = null;

    private int|float|null $maxValue = null;

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
        $this->invalidateRuleSetCache();

        return $this;
    }

    public function isInteger(): bool
    {
        return $this->integerOnly;
    }

    /**
     * Set the minimum allowed value. Emits a `min:<n>` validation rule.
     */
    public function min(int|float $min): self
    {
        $this->minValue = $min;
        $this->invalidateRuleSetCache();

        return $this;
    }

    /**
     * Set the maximum allowed value. Emits a `max:<n>` validation rule.
     */
    public function max(int|float $max): self
    {
        $this->maxValue = $max;
        $this->invalidateRuleSetCache();

        return $this;
    }

    /**
     * Emits `integer` (when {@see integer()} was called) or `numeric` by default,
     * plus optional `min:<n>` and `max:<n>` bounds rules.
     *
     * {@inheritdoc}
     */
    protected function typeRules(): array
    {
        // Nullable first so optional numbers accept an empty submit; AbstractField
        // auto-suppresses it when the field is marked required().
        $rules = [Rule::Nullable, $this->integerOnly ? Rule::Integer : Rule::Numeric];

        if ($this->minValue !== null) {
            $rules[] = new ParameterizedRule('min', (string) $this->minValue);
        }

        if ($this->maxValue !== null) {
            $rules[] = new ParameterizedRule('max', (string) $this->maxValue);
        }

        return $rules;
    }
}
