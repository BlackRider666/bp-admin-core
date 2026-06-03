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
     * When a {@see RelationOptionsProviderContract} is injected, belongsTo /
     * belongsToMany fields without an embedded definition are decorated with
     * an `options` meta key carrying a `list<array{id, label}>` for the UI to
     * render as a select. Embedded relations render their nested fieldset via
     * a different presenter path and are skipped.
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

        if ($this->relationOptionsProvider instanceof RelationOptionsProviderContract) {
            foreach ($fields as $field) {
                $this->decorateRelationOptions($field, []);
            }
        }

        return $fields;
    }

    /**
     * Attach option rows for belongsTo / belongsToMany relations to the field's
     * meta payload. We mutate in place because AbstractField->withMeta merges
     * additively and is fluent on the same instance.
     *
     * For embedded relations the field itself is a fieldset container — it does
     * NOT receive an options list. Instead we build the fully-decorated list of
     * embedded sub-fields (filtered by visibleOnForm(), with options injected)
     * and attach them to the container field via meta key `'embeddedFields'`.
     * The presenter/view reads `$field->meta()['embeddedFields']` to render the
     * nested fieldset WITHOUT repeating `new $embeddedDefinitionClass()->fields()`,
     * which would yield fresh un-decorated instances and lose all options.
     *
     * FK-skip (omitting a belongsTo that points back to the host model) is a
     * presenter-level concern that depends on the host model class — it remains
     * in the view layer where that context is available.
     *
     * The $visited set (map of class-string → true) prevents infinite recursion
     * when a developer misconfigures a cyclic embed (A embeds B embeds A, or A
     * embeds itself). On a cycle the offending embedded definition is silently
     * skipped — the sub-fields it would have produced are absent, which is the
     * safest degradation: the form renders without that subtree rather than
     * crashing with a stack overflow.
     *
     * @param array<class-string, true> $visited Already-processed embedded definition classes
     *                                           on the current recursion path.
     */
    private function decorateRelationOptions(FieldContract $field, array $visited): void
    {
        if (!$this->relationOptionsProvider instanceof RelationOptionsProviderContract) {
            return;
        }
        if (!$field instanceof RelationFieldContract) {
            return;
        }

        if ($field->isEmbedded()) {
            // The embedded field is a fieldset container. Build the decorated
            // sub-field list exactly once and attach it to the container's meta
            // so the renderer can consume it directly.
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

            // Filter to form-visible sub-fields, then decorate each relation sub-field.
            $embeddedFields = array_values(array_filter(
                $embeddedDef->fields(),
                fn(FieldContract $f): bool => $f->visibleOnForm(),
            ));

            foreach ($embeddedFields as $embeddedField) {
                // Pass the $nextVisited set down so cycles at any depth are caught.
                $this->decorateRelationOptions($embeddedField, $nextVisited);
            }

            // Attach the decorated sub-fields to the container so the view
            // reads from here, not from a fresh fields() call.
            $field->withMeta(['embeddedFields' => $embeddedFields]);
            return;
        }

        if (!in_array($field->relationKind(), ['belongsTo', 'belongsToMany'], true)) {
            return;
        }
        if (!$field instanceof AbstractField) {
            // withMeta lives on the abstract base; if a custom field bypasses
            // it we have nowhere to attach options and silently skip.
            return;
        }

        $options = $this->relationOptionsProvider->options($field);
        $field->withMeta(['options' => $options]);
    }
}
