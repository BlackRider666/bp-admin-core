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
     * @param Closure(EntityDefinitionContract, array<string,mixed>, list<string>): void|null $validateRecord
     *                                                                                                        Optional child-record validator. When provided, hasMany/morphMany children are
     *                                                                                                        validated before host persistence. The third argument is a list of field names
     *                                                                                                        that must be skipped during validation (back-FK fields pointing to the host).
     *                                                                                                        Defaults to a no-op (no validation).
     */
    public function __construct(
        private Closure $createRecord,
        private Closure $updateRecord,
        private Closure $resolveDefinition,
        private ?Closure $validateRecord = null,
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

            if ($field->relationKind() === 'hasMany' || $field->relationKind() === 'morphMany') {
                // Validate each child; leave attributes[$name] intact for RelationWriter.
                $this->validateHasManyChildren($definition, $field, $name, $attributes[$name]);
                continue;
            }

            $payload = $attributes[$name];
            $payload = array_merge($payload, $field->state());
            $embeddedDef = ($this->resolveDefinition)((string) $field->embeddedDefinition());

            if ($field->relationKind() === 'belongsTo') {
                // Create child first, then substitute FK id.
                $created = $this->createChildOrPrefixErrors($embeddedDef, $payload, $name);
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

            if ($field->relationKind() === 'hasMany' || $field->relationKind() === 'morphMany') {
                // Validate each child; leave attributes[$name] intact for RelationWriter.
                $this->validateHasManyChildren($definition, $field, $name, $attributes[$name]);
                continue;
            }

            $payload = $attributes[$name];
            $payload = array_merge($payload, $field->state());
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
                    $created = $this->createChildOrPrefixErrors($embeddedDef, $payload, $name);
                    $attributes[$name] = $created->id();
                } else {
                    try {
                        ($this->updateRecord)(
                            $embeddedDef,
                            new EntityKey((string) $existingFkId, $embeddedDef->keyType()),
                            new EntityRecord($embeddedDef, $payload),
                        );
                    } catch (ValidationException $e) {
                        throw new ValidationException($this->prefixErrorKeys($e->errors(), $name));
                    }
                    $attributes[$name] = $existingFkId; // keep existing FK
                }
            } elseif ($field->relationKind() === 'hasOne') {
                $rel = $currentHost->relation($field->relationName());
                $existingId = is_array($rel) ? ($rel['id'] ?? null) : null;

                if ($existingId !== null) {
                    // Existing child — update it eagerly.
                    try {
                        ($this->updateRecord)(
                            $embeddedDef,
                            new EntityKey((string) $existingId, $embeddedDef->keyType()),
                            new EntityRecord($embeddedDef, $payload),
                        );
                    } catch (ValidationException $e) {
                        throw new ValidationException($this->prefixErrorKeys($e->errors(), $name));
                    }
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
     * Validate each child in a hasMany/morphMany payload. Collects all errors
     * and re-throws as a single ValidationException with prefixed keys.
     * No-op when no $validateRecord closure was provided.
     *
     * The back-FK fields (child belongsTo fields pointing to the host model)
     * are passed as a skip list to the validator — the form omits them and the
     * ORM auto-assigns them on write ($host->rel()->create()), so they must not
     * be required during validation.
     *
     * @param array<mixed, mixed> $children
     * @throws ValidationException When any child fails validation.
     */
    private function validateHasManyChildren(
        EntityDefinitionContract $hostDefinition,
        AbstractRelationField $field,
        string $name,
        array $children,
    ): void {
        if (!$this->validateRecord instanceof Closure) {
            return;
        }
        $embeddedDef = ($this->resolveDefinition)((string) $field->embeddedDefinition());
        $skipFields  = $this->backForeignKeyFields($embeddedDef, $hostDefinition->modelClass());

        $errors = [];
        foreach ($children as $i => $child) {
            if (!is_array($child)) {
                continue;
            }
            try {
                ($this->validateRecord)($embeddedDef, array_merge($child, $field->state()), $skipFields);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    $errors[$name . '.' . $i . '.' . $key] = $messages;
                }
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Names of child belongsTo fields pointing back to the host model. The ORM
     * auto-assigns these on write ($host->rel()->create()) and the form omits
     * them, so child validation must skip them — mirroring the view layer.
     *
     * @return list<string>
     */
    private function backForeignKeyFields(EntityDefinitionContract $embeddedDef, string $hostModelClass): array
    {
        $skip = [];
        foreach ($embeddedDef->fields() as $f) {
            if ($f instanceof AbstractRelationField
                && $f->relationKind() === 'belongsTo'
                && $f->target() === $hostModelClass) {
                $skip[] = $f->name();
            }
        }
        return $skip;
    }

    /**
     * Call createRecord and re-throw any ValidationException with prefixed error keys.
     *
     * @param array<string, mixed> $payload
     * @throws ValidationException With prefixed keys on failure.
     */
    private function createChildOrPrefixErrors(
        EntityDefinitionContract $def,
        array $payload,
        string $prefix,
    ): EntityRecordContract {
        try {
            return ($this->createRecord)($def, new EntityRecord($def, $payload));
        } catch (ValidationException $e) {
            throw new ValidationException($this->prefixErrorKeys($e->errors(), $prefix));
        }
    }

    /**
     * Prefix all error keys with "$prefix.".
     *
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function prefixErrorKeys(array $errors, string $prefix): array
    {
        $out = [];
        foreach ($errors as $key => $messages) {
            $out[$prefix . '.' . $key] = $messages;
        }
        return $out;
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
