<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;
use InvalidArgumentException;

/**
 * Has-many relation field with configurable write semantics.
 *
 * Strategy determines how RelationWriter applies the incoming child payload to
 * the existing related set:
 *
 *  - 'replace' (default): delete all existing children, create the new set
 *    from the payload. Maximum-isolation semantics; loses created_at and any
 *    audit columns on rows that survive the swap. Equivalent to the legacy
 *    "sync" behaviour.
 *
 *  - 'merge': diff the incoming payload against the current children by id.
 *    Rows with matching id are updated in place (preserves created_at /
 *    audit columns / soft-delete state). Rows in the payload without an id
 *    are created. Rows present in the current set but absent from the
 *    payload are deleted.
 *
 *  - 'append': add only — incoming rows are inserted; existing children are
 *    left untouched. Useful for log-style relations.
 *
 * Concrete behaviour is implemented by RelationWriter in the framework
 * adapter. This class only carries the strategy flag.
 */
final class HasManyField extends AbstractRelationField
{
    private const ALLOWED_STRATEGIES = ['replace', 'merge', 'append'];

    private string $strategy = 'replace';

    public static function make(string $name, string $target): self
    {
        return new self($name, 'hasMany', $target);
    }

    public function type(): string
    {
        return 'has_many';
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
                "HasManyField strategy must be one of [%s], got '%s'.",
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
