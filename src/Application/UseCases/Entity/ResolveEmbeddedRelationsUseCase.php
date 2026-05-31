<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;
use Closure;
use DomainException;

/**
 * Resolve embedded relation payloads ({@see AbstractRelationField::embed()}) on
 * store/update operations. Framework-pure: collaborators are passed as
 * {@see Closure}s so the same use case can drive Blade, Inertia, or JSON
 * presenters without coupling to Laravel's container.
 *
 *  - belongsTo embed: child is created/updated FIRST, the resulting id is
 *    substituted into the host attributes under the FK column.
 *  - hasOne embed: child is deferred (returned in the `defer` map) so the
 *    presenter/controller can persist it AFTER the host is created and the
 *    host's primary key is known.
 *
 * Owned relations ({@see AbstractRelationField::owns()}) refuse scalar (FK)
 * writes — the only safe write path is a nested embed payload. The use case
 * throws {@see ValidationException} carrying the translation KEY
 * (see {@see self::OWNED_RELATION_REQUIRES_EMBED_PAYLOAD_KEY}); translation
 * happens at the presenter/controller boundary.
 */
final readonly class ResolveEmbeddedRelationsUseCase
{
    /** Sentinel prefix that marks a string as a translation key for the framework adapter. */
    public const I18N_SENTINEL = 'i18n:';

    public const OWNED_RELATION_REQUIRES_EMBED_PAYLOAD_KEY =
        self::I18N_SENTINEL . 'bpadmin::validation.owned_relation_requires_embed_payload';

    /**
     * @param Closure(EntityDefinitionContract, EntityRecordContract): EntityRecordContract $createRecord
     * @param Closure(EntityDefinitionContract, EntityKey, EntityRecordContract): EntityRecordContract $updateRecord
     * @param Closure(string): EntityDefinitionContract $resolveDefinition
     */
    public function __construct(
        private Closure $createRecord,
        private Closure $updateRecord,
        private Closure $resolveDefinition,
    ) {}

    /**
     * Resolve embedded relations on a store (create) operation.
     *
     * @param array<string, mixed> $attributes Raw attributes from request.
     * @return array{
     *   attributes: array<string, mixed>,
     *   defer: array<string, array{field: AbstractRelationField, payload: array<string, mixed>}>
     * }
     * @throws ValidationException When an owned relation receives a scalar value.
     */
    public function resolveOnStore(EntityDefinitionContract $definition, array $attributes): array
    {
        $defer = [];

        foreach ($definition->fields() as $field) {
            if (!$field instanceof AbstractRelationField) {
                continue;
            }
            if (!$field->isEmbedded()) {
                continue;
            }
            $name = $field->name();
            if (!array_key_exists($name, $attributes)) {
                continue;
            }

            $this->assertNotScalarOnOwnedField($field, $name, $attributes[$name]);

            if (!is_array($attributes[$name])) {
                continue;
            }

            $payload = $attributes[$name];
            $embeddedDef = ($this->resolveDefinition)((string) $field->embeddedDefinition());

            if ($field->relationKind() === 'belongsTo') {
                // Create child first, then substitute FK id.
                $created = ($this->createRecord)(
                    $embeddedDef,
                    new EntityRecord($embeddedDef, $payload),
                );
                $attributes[$name] = $created->id();
            } elseif ($field->relationKind() === 'hasOne') {
                // Host first, child second — defer the child.
                unset($attributes[$name]);
                $defer[$name] = ['field' => $field, 'payload' => $payload];
            }
        }

        return ['attributes' => $attributes, 'defer' => $defer];
    }

    /**
     * Resolve embedded relations on an update operation. Existing FK ids cause
     * an update; a previously-null FK causes a create. hasOne payloads are
     * removed from the host attributes and — when no child record exists yet —
     * returned in the `defer` map so the controller can create them after the
     * host record is saved (same deferred pattern as {@see self::resolveOnStore}).
     *
     * @param array<string, mixed> $attributes
     * @return array{
     *   attributes: array<string, mixed>,
     *   defer: array<string, array{field: AbstractRelationField, payload: array<string, mixed>}>
     * }
     * @throws ValidationException When an owned relation receives a scalar value.
     */
    public function resolveOnUpdate(
        EntityDefinitionContract $definition,
        EntityRecordContract $currentHost,
        array $attributes,
    ): array {
        $defer = [];

        foreach ($definition->fields() as $field) {
            if (!$field instanceof AbstractRelationField) {
                continue;
            }
            if (!$field->isEmbedded()) {
                continue;
            }
            $name = $field->name();
            if (!array_key_exists($name, $attributes)) {
                continue;
            }

            $this->assertNotScalarOnOwnedField($field, $name, $attributes[$name]);

            if (!is_array($attributes[$name])) {
                continue;
            }

            $payload = $attributes[$name];
            $embeddedDef = ($this->resolveDefinition)((string) $field->embeddedDefinition());

            if ($field->relationKind() === 'belongsTo') {
                // Disambiguate "no FK loaded" from "explicit null FK". A bare
                // ->get() collapses both into null and would silently create a
                // brand-new related record whenever the host was fetched with a
                // projection that omits the FK column — orphaning the existing
                // child. Require the host to carry the column so we can trust
                // the value.
                if (!$currentHost->hasField(new FieldName($name))) {
                    throw new DomainException(sprintf(
                        'ResolveEmbeddedRelationsUseCase: partial-load not supported for '
                        . 'embedded update of "%s" — host record must include the "%s" '
                        . 'column so the existing FK can be resolved.',
                        $name,
                        $name,
                    ));
                }

                $existingFkId = $currentHost->get($name);

                if ($existingFkId === null) {
                    // FK explicitly null — create a new related record.
                    $created = ($this->createRecord)(
                        $embeddedDef,
                        new EntityRecord($embeddedDef, $payload),
                    );
                    $attributes[$name] = $created->id();
                } else {
                    ($this->updateRecord)(
                        $embeddedDef,
                        new EntityKey((string) $existingFkId, $embeddedDef->keyType()),
                        new EntityRecord($embeddedDef, $payload),
                    );
                    $attributes[$name] = $existingFkId; // keep existing FK
                }
            } elseif ($field->relationKind() === 'hasOne') {
                $rel = $currentHost->relation($field->relationName());
                $existingId = is_array($rel) ? ($rel['id'] ?? null) : null;

                if ($existingId !== null) {
                    // Existing child — update it eagerly.
                    ($this->updateRecord)(
                        $embeddedDef,
                        new EntityKey((string) $existingId, $embeddedDef->keyType()),
                        new EntityRecord($embeddedDef, $payload),
                    );
                } else {
                    // No child yet — defer creation until after the host is saved
                    // so the host's id is available as the FK value (same path as store).
                    $defer[$name] = ['field' => $field, 'payload' => $payload];
                }
                unset($attributes[$name]);
            }
        }

        return ['attributes' => $attributes, 'defer' => $defer];
    }

    /**
     * @throws ValidationException When an owned relation receives a scalar (non-array) value.
     *                             Owned relations must be created/updated through their nested
     *                             embed payload — accepting a foreign FK id would let an admin
     *                             move the FK and then cascade-delete an unrelated record on
     *                             host destroy.
     */
    private function assertNotScalarOnOwnedField(
        AbstractRelationField $field,
        string $name,
        mixed $value,
    ): void {
        if ($field->isOwned() && !is_array($value)) {
            throw new ValidationException([
                $name => [self::OWNED_RELATION_REQUIRES_EMBED_PAYLOAD_KEY],
            ]);
        }
    }
}
