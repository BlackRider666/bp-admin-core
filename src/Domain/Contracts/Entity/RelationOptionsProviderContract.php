<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Fields\RelationFieldContract;

/**
 * Provider of selectable option lists for relation fields (belongsTo /
 * belongsToMany) at form-render time.
 *
 * Implementations live in the infrastructure layer (e.g. Eloquent-backed),
 * Domain only depends on the contract. Returned shape is intentionally flat
 * so it can be JSON-serialised verbatim into form meta — presenters render
 * the labels as plain text.
 *
 * Result rows are `array{id: mixed, label: string}` to allow non-integer keys
 * (UUIDs, ULIDs, composite slugs) without forcing the implementation into a
 * specific key type.
 */
interface RelationOptionsProviderContract
{
    /**
     * Fetch up to $limit `{id, label}` rows for the relation target.
     *
     * Callers should treat $limit as an upper bound — providers may return
     * fewer rows. Providers SHOULD honour the field's `displayField()` /
     * default display attribute when building `label`.
     *
     * @return list<array{id: mixed, label: string}>
     */
    public function options(RelationFieldContract $field, int $limit = 1000): array;
}
