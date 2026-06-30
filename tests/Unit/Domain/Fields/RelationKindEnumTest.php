<?php

declare(strict_types=1);

/**
 * [Slice0] RED тести: enum RelationKind + enum EmbedStrategy.
 *
 * Покриває A1 (extensibility seam via typed predicates).
 * Всі посилання на RelationKind/EmbedStrategy лише всередині тіл замикань —
 * щоб сьют зібрався (collection-safe), але тести падали при запуску (class not found → error → RED).
 *
 * Mapping A-id ↔ тест:
 *   A1 (enum-predicates seam) → всі тести цього файлу
 */

use BlackParadise\CoreAdmin\Domain\Fields\EmbedStrategy;
use BlackParadise\CoreAdmin\Domain\Fields\RelationKind;

// -------------------------------------------------------------------------
// EmbedStrategy — cases
// -------------------------------------------------------------------------

it('[Slice0] EmbedStrategy CreateFirst case exists', function (): void {
    $case = EmbedStrategy::CreateFirst;
    expect($case)->toBeInstanceOf(EmbedStrategy::class);
});

it('[Slice0] EmbedStrategy DeferChild case exists', function (): void {
    $case = EmbedStrategy::DeferChild;
    expect($case)->toBeInstanceOf(EmbedStrategy::class);
});

it('[Slice0] EmbedStrategy ValidateMany case exists', function (): void {
    $case = EmbedStrategy::ValidateMany;
    expect($case)->toBeInstanceOf(EmbedStrategy::class);
});

it('[Slice0] EmbedStrategy None case exists', function (): void {
    $case = EmbedStrategy::None;
    expect($case)->toBeInstanceOf(EmbedStrategy::class);
});

it('[Slice0] EmbedStrategy has exactly four cases', function (): void {
    expect(EmbedStrategy::cases())->toHaveCount(4);
});

// -------------------------------------------------------------------------
// RelationKind — cases exist
// -------------------------------------------------------------------------

it('[Slice0] RelationKind BelongsTo case exists', function (): void {
    $case = RelationKind::BelongsTo;
    expect($case)->toBeInstanceOf(RelationKind::class);
});

it('[Slice0] RelationKind HasOne case exists', function (): void {
    expect(RelationKind::HasOne)->toBeInstanceOf(RelationKind::class);
});

it('[Slice0] RelationKind HasMany case exists', function (): void {
    expect(RelationKind::HasMany)->toBeInstanceOf(RelationKind::class);
});

it('[Slice0] RelationKind MorphMany case exists', function (): void {
    expect(RelationKind::MorphMany)->toBeInstanceOf(RelationKind::class);
});

it('[Slice0] RelationKind BelongsToMany case exists', function (): void {
    expect(RelationKind::BelongsToMany)->toBeInstanceOf(RelationKind::class);
});

it('[Slice0] RelationKind MorphTo case exists', function (): void {
    expect(RelationKind::MorphTo)->toBeInstanceOf(RelationKind::class);
});

it('[Slice0] RelationKind MorphFile case exists', function (): void {
    expect(RelationKind::MorphFile)->toBeInstanceOf(RelationKind::class);
});

it('[Slice0] RelationKind has exactly seven cases', function (): void {
    expect(RelationKind::cases())->toHaveCount(7);
});

// -------------------------------------------------------------------------
// RelationKind::embedStrategy() — truth table
// -------------------------------------------------------------------------

it('[Slice0] RelationKind BelongsTo embedStrategy is CreateFirst', function (): void {
    expect(RelationKind::BelongsTo->embedStrategy())->toBe(EmbedStrategy::CreateFirst);
});

it('[Slice0] RelationKind HasOne embedStrategy is DeferChild', function (): void {
    expect(RelationKind::HasOne->embedStrategy())->toBe(EmbedStrategy::DeferChild);
});

it('[Slice0] RelationKind HasMany embedStrategy is ValidateMany', function (): void {
    expect(RelationKind::HasMany->embedStrategy())->toBe(EmbedStrategy::ValidateMany);
});

it('[Slice0] RelationKind MorphMany embedStrategy is ValidateMany', function (): void {
    expect(RelationKind::MorphMany->embedStrategy())->toBe(EmbedStrategy::ValidateMany);
});

it('[Slice0] RelationKind BelongsToMany embedStrategy is None', function (): void {
    expect(RelationKind::BelongsToMany->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] RelationKind MorphTo embedStrategy is None', function (): void {
    expect(RelationKind::MorphTo->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] RelationKind MorphFile embedStrategy is None', function (): void {
    expect(RelationKind::MorphFile->embedStrategy())->toBe(EmbedStrategy::None);
});

// -------------------------------------------------------------------------
// RelationKind::isSideEffect() — truth table
// -------------------------------------------------------------------------

it('[Slice0] RelationKind BelongsTo isSideEffect is false (host-FK, no side effect)', function (): void {
    expect(RelationKind::BelongsTo->isSideEffect())->toBeFalse();
});

it('[Slice0] RelationKind HasOne isSideEffect is true', function (): void {
    expect(RelationKind::HasOne->isSideEffect())->toBeTrue();
});

it('[Slice0] RelationKind HasMany isSideEffect is true', function (): void {
    expect(RelationKind::HasMany->isSideEffect())->toBeTrue();
});

it('[Slice0] RelationKind MorphMany isSideEffect is true', function (): void {
    expect(RelationKind::MorphMany->isSideEffect())->toBeTrue();
});

it('[Slice0] RelationKind BelongsToMany isSideEffect is true', function (): void {
    expect(RelationKind::BelongsToMany->isSideEffect())->toBeTrue();
});

it('[Slice0] RelationKind MorphTo isSideEffect is false (host-FK, dual-nature)', function (): void {
    expect(RelationKind::MorphTo->isSideEffect())->toBeFalse();
});

it('[Slice0] RelationKind MorphFile isSideEffect is true', function (): void {
    expect(RelationKind::MorphFile->isSideEffect())->toBeTrue();
});

// -------------------------------------------------------------------------
// RelationKind::legacyString() — backward-compat mapping
// -------------------------------------------------------------------------

it('[Slice0] RelationKind BelongsTo legacyString is belongsTo', function (): void {
    expect(RelationKind::BelongsTo->legacyString())->toBe('belongsTo');
});

it('[Slice0] RelationKind HasOne legacyString is hasOne', function (): void {
    expect(RelationKind::HasOne->legacyString())->toBe('hasOne');
});

it('[Slice0] RelationKind HasMany legacyString is hasMany', function (): void {
    expect(RelationKind::HasMany->legacyString())->toBe('hasMany');
});

it('[Slice0] RelationKind MorphMany legacyString is morphMany', function (): void {
    expect(RelationKind::MorphMany->legacyString())->toBe('morphMany');
});

it('[Slice0] RelationKind BelongsToMany legacyString is belongsToMany', function (): void {
    expect(RelationKind::BelongsToMany->legacyString())->toBe('belongsToMany');
});

it('[Slice0] RelationKind MorphTo legacyString is morphTo', function (): void {
    expect(RelationKind::MorphTo->legacyString())->toBe('morphTo');
});

it('[Slice0] RelationKind MorphFile legacyString is morphFile', function (): void {
    expect(RelationKind::MorphFile->legacyString())->toBe('morphFile');
});

// -------------------------------------------------------------------------
// [T1] RelationKind::tryFromLegacy() — reverse lookup from legacy string
// -------------------------------------------------------------------------

it('[T1] RelationKind::tryFromLegacy returns BelongsTo for belongsTo', function (): void {
    expect(RelationKind::tryFromLegacy('belongsTo'))->toBe(RelationKind::BelongsTo);
});

it('[T1] RelationKind::tryFromLegacy returns HasOne for hasOne', function (): void {
    expect(RelationKind::tryFromLegacy('hasOne'))->toBe(RelationKind::HasOne);
});

it('[T1] RelationKind::tryFromLegacy returns HasMany for hasMany', function (): void {
    expect(RelationKind::tryFromLegacy('hasMany'))->toBe(RelationKind::HasMany);
});

it('[T1] RelationKind::tryFromLegacy returns MorphMany for morphMany', function (): void {
    expect(RelationKind::tryFromLegacy('morphMany'))->toBe(RelationKind::MorphMany);
});

it('[T1] RelationKind::tryFromLegacy returns BelongsToMany for belongsToMany', function (): void {
    expect(RelationKind::tryFromLegacy('belongsToMany'))->toBe(RelationKind::BelongsToMany);
});

it('[T1] RelationKind::tryFromLegacy returns MorphTo for morphTo', function (): void {
    expect(RelationKind::tryFromLegacy('morphTo'))->toBe(RelationKind::MorphTo);
});

it('[T1] RelationKind::tryFromLegacy returns MorphFile for morphFile', function (): void {
    expect(RelationKind::tryFromLegacy('morphFile'))->toBe(RelationKind::MorphFile);
});

it('[T1] RelationKind::tryFromLegacy returns null for unknown string', function (): void {
    expect(RelationKind::tryFromLegacy('nope_unknown'))->toBeNull();
});

it('[T1] RelationKind::tryFromLegacy round-trips legacyString for every case', function (): void {
    foreach (RelationKind::cases() as $case) {
        expect(RelationKind::tryFromLegacy($case->legacyString()))->toBe($case);
    }
});
