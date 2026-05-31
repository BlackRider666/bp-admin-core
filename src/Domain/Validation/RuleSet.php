<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Validation;

final class RuleSet
{
    /** @param array<Rule|ParameterizedRule> $rules */
    public function __construct(private array $rules = []) {}

    public function add(Rule|ParameterizedRule $rule): self
    {
        if ($rule instanceof Rule && $this->has($rule)) {
            return $this;
        }

        $this->rules[] = $rule;

        return $this;
    }

    public function has(Rule $rule): bool
    {
        foreach ($this->rules as $existing) {
            if ($existing instanceof Rule && $existing === $rule) {
                return true;
            }
        }

        return false;
    }

    public function remove(Rule $rule): self
    {
        $this->rules = array_values(array_filter(
            $this->rules,
            static fn(Rule|ParameterizedRule $r): bool => !($r instanceof Rule && $r === $rule),
        ));

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->rules === [];
    }

    /** @return array<Rule|ParameterizedRule> */
    public function all(): array
    {
        return $this->rules;
    }

    /** @return array<string> */
    public function toArray(): array
    {
        return array_map(static function (Rule|ParameterizedRule $rule): string {
            if ($rule instanceof Rule) {
                return $rule->value;
            }

            $value = $rule->value;
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            return "{$rule->name}:{$value}";
        }, $this->rules);
    }
}
