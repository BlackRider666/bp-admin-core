<?php

declare(strict_types=1);

/**
 * [Slice0] RED тести: EntityRecord extensibility seam (A1 + A2).
 *
 * Доводить, що EntityRecord дискримінує через предикати поля
 * ($field->isSideEffect(), $field->isRelation(), $field->embedStrategy()), а НЕ через
 * рядкове порівняння типів.
 *
 * Ці тести є СПРАВЖНЬО RED:
 *   - Кастомні поля мають нестандартні типи, невідомі вбудованій логіці.
 *   - Тести перевіряють очікувану поведінку POST-IMPL (через предикати).
 *   - До impl: EntityRecord не враховує ці нестандартні типи → FAIL → RED.
 *
 * Mapping A-id ↔ тест:
 *   A1 (новий тип без правки use cases) → seam test для ResolveEmbeddedRelationsUseCase
 *   A2 (EntityRecord via predicates) → setField/setRelation seam тести
 */

use BlackParadise\CoreAdmin\Application\UseCases\Entity\ResolveEmbeddedRelationsUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;
use BlackParadise\CoreAdmin\Domain\Fields\RelationKind;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;
use Tests\Fixtures\StubEmbeddedDefinition;

/**
 * Helper: creates a minimal EntityDefinitionContract.
 * Unique name to avoid duplicate-function errors.
 *
 * @param array<int, FieldContract> $fields
 */
function slice0_seam_make_definition(array $fields, string $modelClass = 'App\\Models\\SeamModel'): EntityDefinitionContract
{
    return new class ($fields, $modelClass) implements EntityDefinitionContract {
        /** @param array<int, FieldContract> $fields */
        public function __construct(private readonly array $fields, private readonly string $model) {}
        public function name(): string
        {
            return 'seam_entity';
        }
        public function label(): string
        {
            return 'Seam';
        }
        public function keyField(): string
        {
            return 'id';
        }
        public function keyType(): string
        {
            return 'int';
        }
        public function modelClass(): string
        {
            return $this->model;
        }
        public function fields(): array
        {
            return $this->fields;
        }
        public function actions(): array
        {
            return [];
        }
        public function defaultPerPage(): int
        {
            return 15;
        }
        public function searchFields(): array
        {
            return [];
        }

        public function resolveName(): string
        {
            return 'seam_entity';
        }

        public function capabilities(): EntityCapabilities
        {
            return EntityCapabilities::all();
        }

        /**
         * @return array<mixed>
         */
        public function filters(): array
        {
            return [];
        }

        public function repository(): null
        {
            return null;
        }

        public function mutator(): null
        {
            return null;
        }
    };
}

// =========================================================================
// A2 Seam: setField on custom isSideEffect=true field
// =========================================================================

it('[Slice0] setField on custom field with isSideEffect()=true throws DomainException via predicate', function (): void {
    // Custom field: type 'custom_side_effect_type' — unknown to built-in dispatch.
    // Declares isSideEffect()=true so that EntityRecord (predicate-based) throws.
    // Pre-impl: EntityRecord ignores the predicate → no throw → FAIL → RED.
    $customField = new class ('side_eff_rel', 'hasMany', 'App\\Models\\Custom') extends AbstractRelationField {
        public function type(): string
        {
            return 'custom_side_effect_type';
        }

        public function isSideEffect(): bool
        {
            return true;
        }
    };

    $def = slice0_seam_make_definition([$customField]);
    $record = new EntityRecord($def);

    expect(fn() => $record->setField(new FieldName('side_eff_rel'), 'some_value'))
        ->toThrow(DomainException::class);
});

// =========================================================================
// A2 Seam: setRelation on custom isRelation=true field
// =========================================================================

it('[Slice0] setRelation on custom field with isRelation()=true does not throw via predicate', function (): void {
    // Custom field: type 'custom_relation_type' — unknown to built-in dispatch.
    // Declares isRelation()=true so that EntityRecord (predicate-based) allows setRelation.
    // Pre-impl: EntityRecord ignores the predicate → throws DomainException → FAIL → RED.
    $customField = new class ('custom_rel', 'hasMany', 'App\\Models\\Custom') extends AbstractRelationField {
        public function type(): string
        {
            return 'custom_relation_type';
        }

        public function isRelation(): bool
        {
            return true;
        }
    };

    $def = slice0_seam_make_definition([$customField]);
    $record = new EntityRecord($def);

    // Should reach here without exception
    $record->setRelation(new FieldName('custom_rel'), ['some' => 'data']);

    expect($record->relation('custom_rel'))->toBe(['some' => 'data']);
});

// =========================================================================
// A1 Seam: new relation type with DeferChild embedStrategy works in ResolveEmbedded
// =========================================================================

it('[Slice0] ResolveEmbeddedRelationsUseCase defers custom field with DeferChild embedStrategy', function (): void {
    // Custom field: type 'custom_has_one_v2' — NOT matched by current string check ('hasOne').
    // After impl: use case checks $field->embedStrategy() → defers it like HasOne.
    // Pre-impl: use case checks relationKind() === 'hasOne' → 'custom_has_one_v2' !== 'hasOne' → NOT deferred → FAIL → RED.
    $customField = new class ('address', 'custom_has_one_v2', 'App\\Models\\Address') extends AbstractRelationField {
        public function type(): string
        {
            return 'custom_has_one_v2';
        }

        // Override kind() so this anon class still works after IMPL changes kind() to throw
        // on unknown strings. Proves DeferChild dispatch via the documented extension point.
        public function kind(): RelationKind
        {
            return RelationKind::HasOne;
        }
    };

    // Embed a real definition class so isEmbedded()=true
    $customField->embed(StubEmbeddedDefinition::class);

    $def = slice0_seam_make_definition([$customField]);

    $createCalls = [];
    $useCase = new ResolveEmbeddedRelationsUseCase(
        createRecord: function (EntityDefinitionContract $d, EntityRecordContract $r) use (&$createCalls): EntityRecordContract {
            $createCalls[] = $r;
            // Return a record with id=99 so host can receive FK
            return new EntityRecord($d, ['id' => 99]);
        },
        updateRecord: fn(EntityDefinitionContract $d, $key, EntityRecordContract $r): EntityRecordContract => $r,
        resolveDefinition: function (string $class): EntityDefinitionContract {
            /** @var class-string<EntityDefinitionContract> $class */
            return new $class();
        },
    );

    $result = $useCase->resolveOnStore($def, ['address' => ['street' => '123 Main St']]);

    // Post-impl: custom DeferChild field is in 'defer' (not created eagerly, not stripped)
    // Pre-impl: 'address' payload is neither deferred nor created (relationKind check fails) → assert fails → RED
    expect($result['defer'])->toHaveKey('address');
});
