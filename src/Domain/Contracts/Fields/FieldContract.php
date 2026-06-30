<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\EmbedStrategy;
use BlackParadise\CoreAdmin\Domain\Validation\RuleSet;

interface FieldContract
{
    public function name(): string;
    public function label(): string;
    public function type(): string;

    /** @deprecated Use ruleSet() instead. Returns string-based rules for backward compatibility. */
    public function rules(): array;

    public function ruleSet(): RuleSet;
    public function visibleOnList(): bool;
    public function visibleOnForm(): bool;
    public function visibleOnShow(): bool;
    public function isSortable(): bool;
    public function isFilterable(): bool;
    public function meta(): array;

    /**
     * Whether this field accepts incoming write values from HTTP form/JSON.
     *
     * Defaults to true. Set to false (via {@see AbstractField::readonly()}) for
     * display-only computed fields (e.g. `relation_path`, derived totals) or
     * privileged columns (`is_admin`, `role_id`) that must never be writable
     * through the standard CRUD pipeline even if visible.
     *
     * Fields with `visibleOnForm()` === false are also implicitly non-writable
     * — callers must respect BOTH predicates.
     */
    public function writable(): bool;

    // -------------------------------------------------------------------------
    // Behaviour predicates (additive — AbstractField provides safe defaults)
    // -------------------------------------------------------------------------

    /**
     * Whether this field represents a relation (vs. a plain scalar column).
     * True for all AbstractRelationField subclasses and MorphFileField.
     */
    public function isRelation(): bool;

    /**
     * Whether writing this field causes a side effect beyond a simple column
     * write (child upsert, pivot sync, polymorphic file write).
     *
     * EntityRecord::setField() rejects side-effect fields; callers must use
     * setRelation() instead. EloquentEntityMutator filters them from host-level
     * attribute writes.
     */
    public function isSideEffect(): bool;

    /**
     * The embedding strategy for this field when it carries a nested payload
     * in a host create/update request. Drives dispatch in
     * ResolveEmbeddedRelationsUseCase without string-comparing relationKind().
     */
    public function embedStrategy(): EmbedStrategy;

    /**
     * Whether this field is a belongsTo relation pointing back at the given
     * host model class. Used to skip back-FK validation in embedded many-side
     * children (the ORM auto-assigns those columns on write).
     *
     * Returns false for all scalar fields and for non-belongsTo relation fields.
     */
    public function pointsBackTo(string $hostModelClass): bool;

    /**
     * Expand this field's validation rules into a key-indexed map.
     *
     * Non-locale-aware fields return a single entry keyed by their name,
     * ignoring $locales entirely. TranslatableField expands to one entry per
     * locale (dot-notation: `field.locale`). MorphToField expands to two
     * synthetic column keys (`name_type`, `name_id`) with domain-level
     * marker rules.
     *
     * @param array<string> $locales Ordered list of active locale codes
     *                               (e.g. ['en', 'uk']). Non-locale-aware
     *                               fields ignore this parameter.
     * @param string $context 'create' or 'update'. May influence rule
     *                        shape (e.g. MorphToField emits 'required'
     *                        in create, 'nullable' in update).
     * @return array<string, list<string>> Validation-key → flat rule strings.
     */
    public function expandRules(array $locales, string $context): array;
}
