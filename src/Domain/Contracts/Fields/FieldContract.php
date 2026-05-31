<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Fields;

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
}
