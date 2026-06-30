<?php

declare(strict_types=1);

/**
 * [Slice0] RED тести: нові predicate-методи на AbstractField (через скалярні поля).
 *
 * FieldContract отримує нові additive методи:
 *   isRelation(): bool     — AbstractField default false
 *   isSideEffect(): bool   — AbstractField default false
 *   embedStrategy(): EmbedStrategy — AbstractField default EmbedStrategy::None
 *   pointsBackTo(string $hostModelClass): bool — AbstractField default false
 *
 * Всі методи ще не існують → method not found → error → RED.
 *
 * Mapping A-id ↔ тест:
 *   A1 (extensibility via predicates — AbstractField safe defaults) → всі тести цього файлу
 *   A2 (FieldContract additive) → isRelation/isSideEffect дефолти
 */

use BlackParadise\CoreAdmin\Domain\Fields\BooleanField;
use BlackParadise\CoreAdmin\Domain\Fields\EmbedStrategy;
use BlackParadise\CoreAdmin\Domain\Fields\NumberField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;

// -------------------------------------------------------------------------
// TextField — scalar field, all defaults must be false/None
// -------------------------------------------------------------------------

it('[Slice0] TextField isRelation returns false by default', function (): void {
    $field = TextField::make('title');
    expect($field->isRelation())->toBeFalse();
});

it('[Slice0] TextField isSideEffect returns false by default', function (): void {
    $field = TextField::make('title');
    expect($field->isSideEffect())->toBeFalse();
});

it('[Slice0] TextField embedStrategy returns EmbedStrategy None by default', function (): void {
    $field = TextField::make('title');
    expect($field->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] TextField pointsBackTo any class returns false by default', function (): void {
    $field = TextField::make('title');
    expect($field->pointsBackTo('App\\Models\\User'))->toBeFalse();
});

// -------------------------------------------------------------------------
// BooleanField — scalar field
// -------------------------------------------------------------------------

it('[Slice0] BooleanField isRelation returns false by default', function (): void {
    $field = BooleanField::make('active');
    expect($field->isRelation())->toBeFalse();
});

it('[Slice0] BooleanField isSideEffect returns false by default', function (): void {
    $field = BooleanField::make('active');
    expect($field->isSideEffect())->toBeFalse();
});

it('[Slice0] BooleanField embedStrategy returns EmbedStrategy None by default', function (): void {
    $field = BooleanField::make('active');
    expect($field->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] BooleanField pointsBackTo any class returns false by default', function (): void {
    $field = BooleanField::make('active');
    expect($field->pointsBackTo('App\\Models\\Post'))->toBeFalse();
});

// -------------------------------------------------------------------------
// NumberField — scalar field
// -------------------------------------------------------------------------

it('[Slice0] NumberField isRelation returns false by default', function (): void {
    $field = NumberField::make('price');
    expect($field->isRelation())->toBeFalse();
});

it('[Slice0] NumberField isSideEffect returns false by default', function (): void {
    $field = NumberField::make('price');
    expect($field->isSideEffect())->toBeFalse();
});

it('[Slice0] NumberField embedStrategy returns EmbedStrategy None by default', function (): void {
    $field = NumberField::make('price');
    expect($field->embedStrategy())->toBe(EmbedStrategy::None);
});

it('[Slice0] NumberField pointsBackTo any class returns false by default', function (): void {
    $field = NumberField::make('price');
    expect($field->pointsBackTo('App\\Models\\Category'))->toBeFalse();
});
