<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts;

/**
 * Domain contract for value objects (or enums) that carry a human-readable label
 * and expose a static value => label map for UI dropdowns.
 *
 * Why not rely on PHP BackedEnum alone: some host projects express enum-like
 * domain types as custom VO classes (e.g. with `const LABELS` and rich methods)
 * rather than migrate to PHP 8.1 BackedEnum. This contract lets EnumField treat
 * both uniformly.
 *
 * Minimal surface area — framework-agnostic (pure PHP).
 */
interface LabeledValueContract
{
    /**
     * Human-readable label for the current value.
     */
    public function label(): string;

    /**
     * Map of all value => label for all valid cases.
     *
     * @return array<int|string, string>
     */
    public static function options(): array;
}
