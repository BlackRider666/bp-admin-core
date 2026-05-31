<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields\Base;

use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;
use BlackParadise\CoreAdmin\Domain\Validation\RuleSet;

/**
 * Base implementation for all field types.
 *
 * Provides a fluent API for configuring field behaviour. Concrete field
 * classes must implement {@see type()} and may override any method.
 */
abstract class AbstractField implements FieldContract
{
    abstract public function type(): string;

    protected RuleSet $ruleSetInstance;

    public function __construct(
        protected string $name,
        protected ?string $label = null,
        protected array $rules = [],
        protected bool $visibleOnList = true,
        protected bool $visibleOnForm = true,
        protected bool $visibleOnShow = true,
        protected bool $sortable = false,
        protected bool $filterable = false,
        protected array $meta = [],
        protected bool $writable = true,
    ) {
        $this->ruleSetInstance = new RuleSet();

        // Migrate legacy string rules to RuleSet
        foreach ($this->rules as $rule) {
            $enumCase = Rule::tryFrom($rule);
            if ($enumCase !== null) {
                $this->ruleSetInstance->add($enumCase);
            } else {
                // Parameterized rule like "max:255"
                $parts = explode(':', (string) $rule, 2);
                if (count($parts) === 2) {
                    $this->ruleSetInstance->add(new ParameterizedRule($parts[0], $parts[1]));
                } else {
                    $this->ruleSetInstance->add(new ParameterizedRule($rule, null));
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // FieldContract — getters
    // -------------------------------------------------------------------------

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label ?? ucfirst(str_replace('_', ' ', $this->name));
    }

    /** @deprecated Use ruleSet() instead. */
    public function rules(): array
    {
        return $this->ruleSetInstance->toArray();
    }

    public function ruleSet(): RuleSet
    {
        return $this->ruleSetInstance;
    }

    public function visibleOnList(): bool
    {
        return $this->visibleOnList;
    }

    public function visibleOnForm(): bool
    {
        return $this->visibleOnForm;
    }

    public function visibleOnShow(): bool
    {
        return $this->visibleOnShow;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public function writable(): bool
    {
        return $this->writable && $this->visibleOnForm;
    }

    /**
     * Mark this field as read-only at the write boundary. Read-only fields
     * are still rendered (visibleOnForm preserved) but their value is
     * stripped from incoming write payloads to prevent mass-assignment of
     * privileged / computed columns.
     */
    public function readonly(bool $readonly = true): static
    {
        $this->writable = !$readonly;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Fluent setters
    // -------------------------------------------------------------------------

    public function withLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Marks the field as required or not required.
     */
    public function required(bool $required = true): static
    {
        if ($required) {
            $this->ruleSetInstance->remove(Rule::Nullable)->add(Rule::Required);
        } else {
            $this->ruleSetInstance->remove(Rule::Required);
        }

        return $this;
    }

    /**
     * Marks the field as nullable.
     */
    public function nullable(): static
    {
        $this->ruleSetInstance->remove(Rule::Required)->add(Rule::Nullable);

        return $this;
    }

    /**
     * Replaces the entire validation rules with string-based array.
     * @deprecated Prefer using ruleSet() and Rule enum methods.
     */
    public function withRules(array $rules): static
    {
        $this->ruleSetInstance = new RuleSet();
        foreach ($rules as $rule) {
            $enumCase = Rule::tryFrom($rule);
            if ($enumCase !== null) {
                $this->ruleSetInstance->add($enumCase);
            } else {
                $parts = explode(':', (string) $rule, 2);
                if (count($parts) === 2) {
                    $this->ruleSetInstance->add(new ParameterizedRule($parts[0], $parts[1]));
                } else {
                    $this->ruleSetInstance->add(new ParameterizedRule($rule, null));
                }
            }
        }

        return $this;
    }

    /**
     * Adds a domain Rule to the rule set.
     */
    public function addRule(Rule|ParameterizedRule $rule): static
    {
        $this->ruleSetInstance->add($rule);

        return $this;
    }

    public function hideFromList(): static
    {
        $this->visibleOnList = false;

        return $this;
    }

    public function showOnList(): static
    {
        $this->visibleOnList = true;

        return $this;
    }

    public function hideFromForm(): static
    {
        $this->visibleOnForm = false;

        return $this;
    }

    public function showOnForm(): static
    {
        $this->visibleOnForm = true;

        return $this;
    }

    public function hideFromShow(): static
    {
        $this->visibleOnShow = false;

        return $this;
    }

    public function showOnShow(): static
    {
        $this->visibleOnShow = true;

        return $this;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function filterable(bool $filterable = true): static
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function searchable(): static
    {
        return $this->filterable(true);
    }

    public function withMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
