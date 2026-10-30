<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\RelationOptionsProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\RelationFieldContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Fields\RelationKind;

final readonly class BuildFormViewUseCase
{
    public function __construct(
        private AuthorizationProviderContract $authorizationProvider,
        private EntityDefinitionContract $entityDefinition,
        private ?RelationOptionsProviderContract $relationOptionsProvider = null,
    ) {}

    /**
     * Build the list of form-visible fields for the given action.
     *
     * Callers MUST pass an explicit action — the controller knows whether it
     * is rendering a create or edit form and is the right place to make that
     * decision. Defaulting here would make a privileged action (`'create'`)
     * the silent fallback for unrelated bugs.
     *
     * For embedded relation fields, `meta()['embeddedFields']` is ALWAYS
     * populated regardless of whether a RelationOptionsProvider is injected
     * (A8 invariant). Sub-fields are filtered by visibleOnForm() and must not
     * point back to the host model class (FK-skip via BelongsTo + target()
     * check — A7). The cycle guard prevents infinite recursion on cyclic embeds.
     *
     * When a {@see RelationOptionsProviderContract} is injected, non-embedded
     * belongsTo / belongsToMany fields are additionally decorated with an
     * `options` meta key. Embedded container fields themselves never receive
     * an options list.
     *
     * @return array<FieldContract>
     * @throws UnauthorizedException
     */
    public function execute(string $action): array
    {
        if (!$this->authorizationProvider->can($action, $this->entityDefinition)) {
            throw new UnauthorizedException($this->entityDefinition->name(), $action);
        }

        $fields = array_values(array_filter(
            $this->entityDefinition->fields(),
            fn(FieldContract $field): bool => $field->visibleOnForm(),
        ));

        $hostModelClass = $this->entityDefinition->modelClass();

        foreach ($fields as $field) {
            $this->decorateField($field, $hostModelClass, []);
        }

        return $fields;
    }

    /**
     * Process a single field for the form view.
     *
     * - Embedded containers: always build embeddedFields list (A8), optionally
     *   decorate sub-field options when the provider is available.
     * - Non-embedded BelongsTo / BelongsToMany: decorate options (provider-gated).
     * - MorphTo: decorate morphOptions (provider-gated).
     * - All others (non-relation, HasMany, HasOne, etc.): no-op.
     *
     * @param array<class-string, true> $visited Already-processed embedded definition classes
     *                                           on the current recursion path (cycle guard).
     */
    private function decorateField(FieldContract $field, ?string $hostModelClass, array $visited): void
    {
        if (!$field instanceof RelationFieldContract) {
            return;
        }

        if ($field->isEmbedded()) {
            $this->buildEmbeddedFields($field, $hostModelClass, $visited);
            return;
        }

        // Non-embedded relation: option decoration is provider-gated.
        if (!$this->relationOptionsProvider instanceof RelationOptionsProviderContract) {
            return;
        }

        if ($field->kind() === RelationKind::MorphTo) {
            if ($field instanceof AbstractField) {
                $field->withMeta(['morphOptions' => $this->relationOptionsProvider->morphOptions($field)]);
            }
            return;
        }

        if (!in_array($field->kind(), [RelationKind::BelongsTo, RelationKind::BelongsToMany], true)) {
            return;
        }
        if (!$field instanceof AbstractField) {
            // withMeta lives on the abstract base; if a custom field bypasses
            // it we have nowhere to attach options and silently skip.
            return;
        }

        $field->withMeta(['options' => $this->relationOptionsProvider->options($field)]);
    }

    /**
     * Build the embeddedFields list for an embedded container field and attach
     * it to the field's meta. Runs ALWAYS — independent of the provider (A8).
     *
     * Sub-fields are filtered to:
     * 1. visibleOnForm() — only form-visible sub-fields are included.
     * 2. Not pointing back to the host model class (A7 FK-skip): sub-fields whose
     *    $f->pointsBackTo($hostModelClass) is true (a BelongsTo to the host) are
     *    excluded. $hostModelClass is the modelClass() of the definition that owns this
     *    embed. When recursing into a nested embedded definition D, the host for
     *    D's own children becomes D::modelClass() so back-FKs are correctly
     *    detected at every level of nesting.
     *
     * The $visited cycle-guard (map of class-string → true) prevents infinite
     * recursion on cyclic embed graphs (A embeds B embeds A, or self-reference).
     * On a cycle the offending sub-tree is silently skipped — the form renders
     * without that subtree rather than crashing with a stack overflow.
     *
     * The presenter/view reads `$field->meta()['embeddedFields']` to render the
     * nested fieldset WITHOUT repeating `new $embeddedDefinitionClass()->fields()`,
     * which would yield fresh un-decorated instances and lose all injected options.
     *
     * @param array<class-string, true> $visited
     */
    private function buildEmbeddedFields(RelationFieldContract $field, ?string $hostModelClass, array $visited): void
    {
        $embeddedDefinitionClass = $field->embeddedDefinition();
        if ($embeddedDefinitionClass === null || !($field instanceof AbstractField)) {
            return;
        }

        // Cycle guard: if this definition class is already on the current
        // recursion path, skip it entirely to prevent a stack overflow.
        if (isset($visited[$embeddedDefinitionClass])) {
            return;
        }

        // Mark this definition as visited before descending so that any
        // embedded field inside it pointing back to the same class is caught.
        // Build a new array so the type annotation is preserved for PHPStan.
        /** @var array<class-string, true> $nextVisited */
        $nextVisited = $visited + [$embeddedDefinitionClass => true];

        /** @var EntityDefinitionContract $embeddedDef */
        $embeddedDef = new $embeddedDefinitionClass();

        // The embedded definition's own modelClass is the host for ITS children's
        // FK-skip check when recursing one level deeper.
        $embeddedHostModelClass = $embeddedDef->modelClass();

        // Filter sub-fields: visibleOnForm() AND not pointing back to the parent
        // host model (A7). Back-FK detection delegates to the field's own
        // pointsBackTo() predicate — it lives on FieldContract (true only for a
        // BelongsTo whose target() is the host, false for everything else), so
        // every field type honours the same single source of truth and there is
        // no inlined relation-kind check to drift out of sync.
        $embeddedFields = array_values(array_filter(
            $embeddedDef->fields(),
            fn(FieldContract $f): bool => $f->visibleOnForm() && ($hostModelClass === null || !$f->pointsBackTo($hostModelClass)),
        ));

        // Recursively process each sub-field: build their embeddedFields and
        // decorate options. Pass the embedded def's modelClass as host so that
        // back-FKs at the next level are filtered correctly.
        foreach ($embeddedFields as $embeddedField) {
            $this->decorateField($embeddedField, $embeddedHostModelClass, $nextVisited);
        }

        // Attach the decorated sub-fields to the container so the view reads
        // from here, not from a fresh $embeddedDefinitionClass()->fields() call.
        $field->withMeta(['embeddedFields' => $embeddedFields]);
    }
}
