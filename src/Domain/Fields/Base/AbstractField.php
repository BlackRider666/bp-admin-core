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
 *
 * Auto-rules (type-derived validation rules) are provided by {@see typeRules()}
 * which concrete classes override. They are always merged AFTER the explicit
 * rule set so that developer-supplied rules take precedence in ordering, and
 * they are never wiped by {@see withRules()} (which only resets the explicit
 * set). Auto-rules appear in {@see rules()} and {@see ruleSet()} output.
 */
abstract class AbstractField implements FieldContract
{
    abstract public function type(): string;

    protected RuleSet $ruleSetInstance;

    /** Maximum string/text length for auto-rule emission. Null = no auto max. */
    protected ?int $maxLengthValue = null;

    /**
     * Memoized result of path-2 ruleSet() (when typeRules() !== []).
     * Null means the cache is invalid and must be recomputed on next call.
     * Invalidated by every mutator that can change the merged rule output.
     */
    private ?RuleSet $mergedRuleSetCache = null;

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
        $this->ruleSetInstance = $this->parseLegacyRules($this->rules);
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
        if ($this->label !== null) {
            return $this->label;
        }
        // Insert spaces before camelCase humps (e.g. "executiveEditor" → "executive Editor").
        $spaced = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', $this->name) ?? $this->name;
        // Split on underscores.
        $spaced = str_replace('_', ' ', $spaced);
        // Collapse multiple spaces and trim.
        $spaced = preg_replace('/\s+/', ' ', trim($spaced)) ?? $spaced;
        return ucfirst($spaced);
    }

    /**
     * Returns type-derived auto-rules that are always appended after the
     * explicit rule set. Concrete classes override this to emit structural
     * constraints (e.g. `numeric`, `max:N`) without touching the explicit set.
     *
     * @return array<Rule|ParameterizedRule>
     */
    protected function typeRules(): array
    {
        return [];
    }

    /** @deprecated Use ruleSet() instead. */
    public function rules(): array
    {
        return $this->ruleSet()->toArray();
    }

    public function ruleSet(): RuleSet
    {
        $typeRules = $this->typeRules();
        if ($typeRules === []) {
            return $this->ruleSetInstance;
        }

        // Path-2: typeRules() is non-empty — memoize the merged result so
        // repeat calls (e.g. during validation building) avoid clone+merge.
        // The cache is invalidated by every mutator that can change the output.
        if ($this->mergedRuleSetCache instanceof RuleSet) {
            return $this->mergedRuleSetCache;
        }

        // Merge auto-rules into a cloned set so $ruleSetInstance stays pristine
        // (subsequent withRules() calls must still only reset the explicit rules).
        $merged = clone $this->ruleSetInstance;
        // A field explicitly marked required() must never also receive an
        // auto "nullable" type-rule (which Laravel treats as winning, making the
        // field effectively optional). Suppress auto-Nullable when Required is set.
        $hasRequired = $merged->has(Rule::Required);
        foreach ($typeRules as $autoRule) {
            if ($hasRequired && $autoRule === Rule::Nullable) {
                continue;
            }
            $merged->add($autoRule);
        }

        return $this->mergedRuleSetCache = $merged;
    }

    /**
     * Invalidate the memoized merged RuleSet cache.
     *
     * Must be called by every mutator in this class (and in subclasses) that
     * can affect the output of typeRules() or the contents of ruleSetInstance,
     * so that the next ruleSet() call recomputes the merged result.
     */
    protected function invalidateRuleSetCache(): void
    {
        $this->mergedRuleSetCache = null;
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
        $this->invalidateRuleSetCache();

        return $this;
    }

    /**
     * Marks the field as nullable.
     */
    public function nullable(): static
    {
        $this->ruleSetInstance->remove(Rule::Required)->add(Rule::Nullable);
        $this->invalidateRuleSetCache();

        return $this;
    }

    /**
     * Replaces the entire validation rules with string-based array.
     * @deprecated Prefer using ruleSet() and Rule enum methods.
     */
    public function withRules(array $rules): static
    {
        $this->ruleSetInstance = $this->parseLegacyRules($rules);
        $this->invalidateRuleSetCache();

        return $this;
    }

    /**
     * Parse an array of legacy string rules (e.g. `['required', 'max:255']`)
     * into a fresh {@see RuleSet} instance.
     *
     * Extracted from the constructor and {@see withRules()} to keep the
     * migration logic in a single place. Behaviour is unchanged.
     *
     * @param array<int|string, string> $rules
     */
    private function parseLegacyRules(array $rules): RuleSet
    {
        $set = new RuleSet();

        foreach ($rules as $rule) {
            $enumCase = Rule::tryFrom($rule);
            if ($enumCase !== null) {
                $set->add($enumCase);
            } else {
                // Parameterized rule like "max:255"
                $parts = explode(':', $rule, 2);
                if (count($parts) === 2) {
                    $set->add(new ParameterizedRule($parts[0], $parts[1]));
                } else {
                    $set->add(new ParameterizedRule($rule, null));
                }
            }
        }

        return $set;
    }

    /**
     * Adds a domain Rule to the rule set.
     */
    public function addRule(Rule|ParameterizedRule $rule): static
    {
        $this->ruleSetInstance->add($rule);
        $this->invalidateRuleSetCache();

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

    /**
     * Set the maximum allowed length for string/text fields.
     * Emits a `max:<n>` validation rule via {@see typeRules()}.
     *
     * Has no effect on field types that do not produce string data
     * (e.g. boolean, date) — subclasses decide whether to honour it.
     */
    public function maxLength(int $length): static
    {
        $this->maxLengthValue = $length;
        $this->invalidateRuleSetCache();

        return $this;
    }

    public function withMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
