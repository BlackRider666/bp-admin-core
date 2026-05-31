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
                $this->decorateRelationOptions($field);
            }
        }

        return $fields;
    }

    /**
     * Attach option rows for belongsTo / belongsToMany relations to the field's
     * meta payload. Embedded relations use a different render path and are
     * skipped. We mutate in place because AbstractField->withMeta merges
     * additively and is fluent on the same instance.
     */
    private function decorateRelationOptions(FieldContract $field): void
    {
        if (!$this->relationOptionsProvider instanceof RelationOptionsProviderContract) {
            return;
        }
        if (!$field instanceof RelationFieldContract) {
            return;
        }
        if ($field->isEmbedded()) {
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
