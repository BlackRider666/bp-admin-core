<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;
use InvalidArgumentException;

/**
 * Morph-many relation field with configurable write semantics.
 *
 * See {@see HasManyField} for full strategy semantics — the same three modes
 * ('replace' | 'merge' | 'append') apply. Morph columns (morph_type /
 * morph_id) are auto-populated by Eloquent regardless of strategy.
 */
final class MorphManyField extends AbstractRelationField
{
    private const ALLOWED_STRATEGIES = ['replace', 'merge', 'append'];

    private string $strategy = 'replace';

    public static function make(string $name, string $target): self
    {
        return new self($name, 'morphMany', $target);
    }

    public function type(): string
    {
        return 'morph_many';
    }

    /**
     * Configure the write strategy applied by RelationWriter when this
     * relation's payload is persisted.
     *
     * @param 'replace'|'merge'|'append' $strategy
     * @throws InvalidArgumentException When $strategy is not in the allowlist.
     */
    public function strategy(string $strategy): self
    {
        if (!in_array($strategy, self::ALLOWED_STRATEGIES, true)) {
            throw new InvalidArgumentException(sprintf(
                "MorphManyField strategy must be one of [%s], got '%s'.",
                implode(', ', self::ALLOWED_STRATEGIES),
                $strategy,
            ));
        }
        $this->strategy = $strategy;
        return $this;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }
}
