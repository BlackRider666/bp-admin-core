<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

/**
 * Typed enumeration of all supported relation kinds.
 *
 * Carries domain logic that was previously scattered across string-switch
 * blocks in use cases (embed strategy, side-effect classification, legacy
 * string backward compat). All callers should discriminate via these methods
 * rather than comparing raw relationKind() strings.
 */
enum RelationKind
{
    /** Inverse many-to-one — host owns the FK column. Not a side effect. */
    case BelongsTo;

    /** One-to-one parent → child — child owns the FK. Side effect. */
    case HasOne;

    /** One-to-many parent → children — children own the FK. Side effect. */
    case HasMany;

    /** Polymorphic one-to-many — children own morph columns. Side effect. */
    case MorphMany;

    /** Many-to-many via pivot table. Side effect (pivot sync). */
    case BelongsToMany;

    /** Polymorphic belongs-to — host owns _type + _id columns. Not a side effect. */
    case MorphTo;

    /** Polymorphic file attachment stored in a normalised files table. Side effect. */
    // In-repo: MorphFileField extends AbstractField and overrides predicates directly — this case exists for completeness / third-party morph-file relation fields.
    case MorphFile;

    // -------------------------------------------------------------------------
    // Domain methods
    // -------------------------------------------------------------------------

    /**
     * The embedding strategy a use case should apply when this field carries
     * a nested payload within a host create/update request.
     */
    public function embedStrategy(): EmbedStrategy
    {
        return match ($this) {
            self::BelongsTo                  => EmbedStrategy::CreateFirst,
            self::HasOne                     => EmbedStrategy::DeferChild,
            self::HasMany, self::MorphMany   => EmbedStrategy::ValidateMany,
            self::BelongsToMany,
            self::MorphTo,
            self::MorphFile                  => EmbedStrategy::None,
        };
    }

    /**
     * Whether writing this relation causes a side effect beyond a simple
     * column write on the host record (child upsert, pivot sync, etc.).
     *
     * false only for BelongsTo and MorphTo — both store their FK directly
     * on the host row, so setField() accepts them.
     */
    public function isSideEffect(): bool
    {
        return match ($this) {
            self::BelongsTo, self::MorphTo                => false,
            self::HasOne, self::HasMany, self::MorphMany,
            self::BelongsToMany, self::MorphFile          => true,
        };
    }

    /**
     * The legacy string representation consumed by framework adapters
     * that have not yet migrated to kind()-based dispatch.
     */
    public function legacyString(): string
    {
        return match ($this) {
            self::BelongsTo     => 'belongsTo',
            self::HasOne        => 'hasOne',
            self::HasMany       => 'hasMany',
            self::MorphMany     => 'morphMany',
            self::BelongsToMany => 'belongsToMany',
            self::MorphTo       => 'morphTo',
            self::MorphFile     => 'morphFile',
        };
    }

    /**
     * Resolve a legacy relation-kind string back to its typed case. Inverse of
     * {@see legacyString()}. Returns null for unrecognised strings so callers can
     * decide whether to fail loud (see {@see Base\AbstractRelationField::kind()}).
     */
    public static function tryFromLegacy(string $legacy): ?self
    {
        return match ($legacy) {
            'belongsTo'     => self::BelongsTo,
            'hasOne'        => self::HasOne,
            'hasMany'       => self::HasMany,
            'morphMany'     => self::MorphMany,
            'belongsToMany' => self::BelongsToMany,
            'morphTo'       => self::MorphTo,
            'morphFile'     => self::MorphFile,
            default         => null,
        };
    }
}
