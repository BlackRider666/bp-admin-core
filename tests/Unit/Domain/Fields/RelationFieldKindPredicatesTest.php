<?php

declare(strict_types=1);

/**
 * [Slice0] RED тести: kind() + predicate-методи на concrete relation fields.
 *
 * Truth table (з spec):
 * | Field              | kind()       | isRelation | isSideEffect | embedStrategy | pointsBackTo($h)       |
 * |--------------------|--------------|------------|--------------|---------------|------------------------|
 * | BelongsToField     | BelongsTo    | true       | false        | CreateFirst   | true ⇔ target()===$h   |
 * | HasOneField        | HasOne       | true       | true         | DeferChild    | false                  |
 * | HasManyField       | HasMany      | true       | true         | ValidateMany  | false                  |
 * | MorphManyField     | MorphMany    | true       | true         | ValidateMany  | false                  |
 * | BelongsToManyField | BelongsToMany| true       | true         | None          | false                  |
 * | MorphToField       | MorphTo      | true       | false        | None          | false                  |
 * | MorphFileField     | — (no kind)  | true       | true         | None          | false                  |
 * | Scalar fields      | —            | false      | false        | None          | false                  |
 *
 * Всі нові методи ще не існують → method not found → error → RED.
 * Виняток: MorphFileField::kind() не повинно існувати (extends AbstractField, не relation-base).
 *
 * Mapping A-id ↔ тест:
 *   A1 (field predicates — seam для callers) → всі тести цього файлу
 *   A2 (kind() shim → relationKind() backward compat) → "shim" тести
 */

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\EmbedStrategy;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasOneField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphFileField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphManyField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphToField;
use BlackParadise\CoreAdmin\Domain\Fields\RelationKind;
use InvalidArgumentException;

// =========================================================================
// BelongsToField
// =========================================================================

it('[Slice0] BelongsToField kind() returns RelationKind BelongsTo', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    expect($field->kind())->toBe(RelationKind::BelongsTo);
});

it('[Slice0] BelongsToField isRelation() returns true', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    expect($field->isRelation())->toBeTrue();
});

it('[Slice0] BelongsToField isSideEffect() returns false (has host-FK)', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    expect($field->isSideEffect())->toBeFalse();
});

it('[Slice0] BelongsToField embedStrategy() returns EmbedStrategy CreateFirst', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    expect($field->embedStrategy())->toBe(EmbedStrategy::CreateFirst);
});

it('[Slice0] BelongsToField pointsBackTo() returns true when target matches host', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    expect($field->pointsBackTo('App\\Models\\Author'))->toBeTrue();
});

it('[Slice0] BelongsToField pointsBackTo() returns false when target does not match host', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    expect($field->pointsBackTo('App\\Models\\User'))->toBeFalse();
});

it('[Slice0] BelongsToField kind()->legacyString() returns belongsTo (shim backward compat)', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    expect($field->kind()->legacyString())->toBe('belongsTo');
});

// =========================================================================
// HasOneField
// =========================================================================

it('[Slice0] HasOneField kind() returns RelationKind HasOne', function (): void {
    $field = HasOneField::make('profile', 'App\\Models\\Profile');
    expect($field->kind())->toBe(RelationKind::HasOne);
});

it('[Slice0] HasOneField isRelation() returns true', function (): void {
    $field = HasOneField::make('profile', 'App\\Models\\Profile');
    expect($field->isRelation())->toBeTrue();
});

it('[Slice0] HasOneField isSideEffect() returns true', function (): void {
    $field = HasOneField::make('profile', 'App\\Models\\Profile');
    expect($field->isSideEffect())->toBeTrue();
});

it('[Slice0] HasOneField embedStrategy() returns EmbedStrategy DeferChild', function (): void {
    $field = HasOneField::make('profile', 'App\\Models\\Profile');
    expect($field->embedStrategy())->toBe(EmbedStrategy::DeferChild);
});

it('[Slice0] HasOneField pointsBackTo() returns false (child holds FK, not host)', function (): void {
    $field = HasOneField::make('profile', 'App\\Models\\Profile');
    expect($field->pointsBackTo('App\\Models\\User'))->toBeFalse();
});

it('[Slice0] HasOneField kind()->legacyString() returns hasOne (shim backward compat)', function (): void {
    $field = HasOneField::make('profile', 'App\\Models\\Profile');
    expect($field->kind()->legacyString())->toBe('hasOne');
});

// =========================================================================
// HasManyField
// =========================================================================

it('[Slice0] HasManyField kind() returns RelationKind HasMany', function (): void {
    $field = HasManyField::make('posts', 'App\\Models\\Post');
    expect($field->kind())->toBe(RelationKind::HasMany);
});

it('[Slice0] HasManyField isRelation() returns true', function (): void {
    $field = HasManyField::make('posts', 'App\\Models\\Post');
    expect($field->isRelation())->toBeTrue();
});

it('[Slice0] HasManyField isSideEffect() returns true', function (): void {
    $field = HasManyField::make('posts', 'App\\Models\\Post');
    expect($field->isSideEffect())->toBeTrue();
});

it('[Slice0] HasManyField embedStrategy() returns EmbedStrategy ValidateMany', function (): void {
    $field = HasManyField::make('posts', 'App\\Models\\Post');
    expect($field->embedStrategy())->toBe(EmbedStrategy::ValidateMany);
});

it('[Slice0] HasManyField pointsBackTo() returns false', function (): void {
    $field = HasManyField::make('posts', 'App\\Models\\Post');
    expect($field->pointsBackTo('App\\Models\\User'))->toBeFalse();
});

it('[Slice0] HasManyField kind()->legacyString() returns hasMany (shim backward compat)', function (): void {
    $field = HasManyField::make('posts', 'App\\Models\\Post');
    expect($field->kind()->legacyString())->toBe('hasMany');
});

// =========================================================================
// MorphManyField
// =========================================================================

it('[Slice0] MorphManyField kind() returns RelationKind MorphMany', function (): void {
    $field = MorphManyField::make('comments', 'App\\Models\\Comment');
    expect($field->kind())->toBe(RelationKind::MorphMany);
});

it('[Slice0] MorphManyField isRelation() returns true', function (): void {
    $field = MorphManyField::make('comments', 'App\\Models\\Comment');
    expect($field->isRelation())->toBeTrue();
});

it('[Slice0] MorphManyField isSideEffect() returns true', function (): void {
    $field = MorphManyField::make('comments', 'App\\Models\\Comment');
    expect($field->isSideEffect())->toBeTrue();
});

it('[Slice0] MorphManyField embedStrategy() returns EmbedStrategy ValidateMany', function (): void {
    $field = MorphManyField::make('comments', 'App\\Models\\Comment');
    expect($field->embedStrategy())->toBe(EmbedStrategy::ValidateMany);
});

it('[Slice0] MorphManyField pointsBackTo() returns false', function (): void {
    $field = MorphManyField::make('comments', 'App\\Models\\Comment');
    expect($field->pointsBackTo('App\\Models\\Post'))->toBeFalse();
});

it('[Slice0] MorphManyField kind()->legacyString() returns morphMany (shim backward compat)', function (): void {
    $field = MorphManyField::make('comments', 'App\\Models\\Comment');
    expect($field->kind()->legacyString())->toBe('morphMany');
});

// =========================================================================
// BelongsToManyField
// =========================================================================

it('[Slice0] BelongsToManyField kind() returns RelationKind BelongsToMany', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    expect($field->kind())->toBe(RelationKind::BelongsToMany);
});

it('[Slice0] BelongsToManyField isRelation() returns true', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    expect($field->isRelation())->toBeTrue();
});

it('[Slice0] BelongsToManyField isSideEffect() returns true', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    expect($field->isSideEffect())->toBeTrue();
});

it('[Slice0] BelongsToManyField embedStrategy() returns EmbedStrategy None', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    expect($field->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] BelongsToManyField pointsBackTo() returns false', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    expect($field->pointsBackTo('App\\Models\\User'))->toBeFalse();
});

it('[Slice0] BelongsToManyField kind()->legacyString() returns belongsToMany (shim backward compat)', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    expect($field->kind()->legacyString())->toBe('belongsToMany');
});

// =========================================================================
// MorphToField
// =========================================================================

it('[Slice0] MorphToField kind() returns RelationKind MorphTo', function (): void {
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    expect($field->kind())->toBe(RelationKind::MorphTo);
});

it('[Slice0] MorphToField isRelation() returns true', function (): void {
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    expect($field->isRelation())->toBeTrue();
});

it('[Slice0] MorphToField isSideEffect() returns false (host holds type+id FKs)', function (): void {
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    expect($field->isSideEffect())->toBeFalse();
});

it('[Slice0] MorphToField embedStrategy() returns EmbedStrategy None', function (): void {
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    expect($field->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] MorphToField pointsBackTo() returns false', function (): void {
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    expect($field->pointsBackTo('App\\Models\\User'))->toBeFalse();
});

it('[Slice0] MorphToField kind()->legacyString() returns morphTo (shim backward compat)', function (): void {
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    expect($field->kind()->legacyString())->toBe('morphTo');
});

// =========================================================================
// MorphFileField — extends AbstractField, NOT AbstractRelationField — no kind()
// =========================================================================

it('[Slice0] MorphFileField isRelation() returns true (special: AbstractField subtype with relation semantics)', function (): void {
    $field = MorphFileField::make('avatar');
    expect($field->isRelation())->toBeTrue();
});

it('[Slice0] MorphFileField isSideEffect() returns true', function (): void {
    $field = MorphFileField::make('avatar');
    expect($field->isSideEffect())->toBeTrue();
});

it('[Slice0] MorphFileField embedStrategy() returns EmbedStrategy None', function (): void {
    $field = MorphFileField::make('avatar');
    expect($field->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] MorphFileField pointsBackTo() returns false', function (): void {
    $field = MorphFileField::make('avatar');
    expect($field->pointsBackTo('App\\Models\\User'))->toBeFalse();
});

// =========================================================================
// [T1] AbstractRelationField::kind() — throws on unknown relationKind
// =========================================================================

it('[T1] AbstractRelationField kind() throws InvalidArgumentException for unknown relationKind', function (): void {
    $field = new class ('x_rel', 'totally_unknown_kind', 'App\\Models\\X') extends AbstractRelationField {
        public function type(): string
        {
            return 'x';
        }
    };
    expect(fn(): RelationKind => $field->kind())->toThrow(InvalidArgumentException::class);
});

it('[T1] AbstractRelationField kind() resolves hasMany without throwing (known legacy string survives)', function (): void {
    $field = new class ('posts', 'hasMany', 'App\\Models\\Post') extends AbstractRelationField {
        public function type(): string
        {
            return 'has_many_test';
        }
    };
    expect($field->kind())->toBe(RelationKind::HasMany);
});
