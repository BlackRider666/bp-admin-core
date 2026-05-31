<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

/**
 * Canonical catalogue of relation field-type strings.
 *
 * Used by EntityRecord (to enforce setField/setRelation invariants) and by
 * EloquentEntityMutator (to filter column attributes during persistence).
 *
 * Keep in sync with FieldTypeRegistry registrations in DashboardServiceProvider.
 */
final class RelationFieldTypes
{
    /**
     * All field types that represent relations (vs. plain columns).
     *
     * belongs_to / morph_to are dual-nature: they ARE relations and they ALSO
     * have a column-level FK on the host record, so setField() also accepts them.
     */
    public const ALL = [
        'belongs_to',
        'belongs_to_many',
        'has_many',
        'has_one',
        'morph_to',
        'morph_many',
        'morph_file',
    ];

    /**
     * Relations that produce side effects beyond a simple column write
     * (pivot sync, child upsert, polymorphic file write).
     *
     * setField() rejects these — caller must use setRelation().
     * EloquentEntityMutator filters them out of host-level attribute writes.
     */
    public const SIDE_EFFECT = [
        'belongs_to_many',
        'has_many',
        'has_one',
        'morph_many',
        'morph_file',
    ];

    public static function isRelation(string $type): bool
    {
        return in_array($type, self::ALL, true);
    }

    public static function isSideEffect(string $type): bool
    {
        return in_array($type, self::SIDE_EFFECT, true);
    }
}
