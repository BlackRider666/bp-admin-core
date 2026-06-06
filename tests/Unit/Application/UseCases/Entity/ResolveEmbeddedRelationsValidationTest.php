<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\ResolveEmbeddedRelationsUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\StubEmbeddedDefinition;

/**
 * A5 (core): ResolveEmbeddedRelationsUseCase — embedded hasMany validation + belongsTo prefix.
 *
 * Currently the use case has no validateRecord closure and no hasMany validation branch,
 * so both tests are RED (error: wrong ctor arity / no validation logic).
 */
final class ResolveEmbeddedRelationsValidationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function definitionWithFields(array $fields): EntityDefinitionContract
    {
        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('name')->willReturn('hosts');
        $def->method('keyField')->willReturn('id');
        $def->method('keyType')->willReturn('int');
        $def->method('fields')->willReturn($fields);
        return $def;
    }

    /**
     * Build a use case that accepts the 4th validateRecord closure (not yet in the ctor).
     *
     * @param callable $validateRecord fn(EntityDefinitionContract, array): void — throws ValidationException on failure
     */
    private function makeUseCaseWithValidation(
        callable $validateRecord,
        ?callable $createReturn = null,
    ): ResolveEmbeddedRelationsUseCase {
        $embeddedDef = new StubEmbeddedDefinition();

        $createClosure = function (
            EntityDefinitionContract $def,
            EntityRecordContract $rec,
        ) use ($createReturn): EntityRecordContract {
            if ($createReturn !== null) {
                return $createReturn($def, $rec);
            }
            return new EntityRecord($def, ['id' => 99] + $rec->attributes());
        };

        $updateClosure = (fn(EntityDefinitionContract $def, EntityKey $key, EntityRecordContract $rec): EntityRecordContract => new EntityRecord($def, ['id' => $key->value] + $rec->attributes()));

        $resolveClosure = fn(string $defClass): EntityDefinitionContract => $embeddedDef;

        // The use case must accept a 4th $validateRecord closure (added by fix).
        return new ResolveEmbeddedRelationsUseCase(
            createRecord: $createClosure,
            updateRecord: $updateClosure,
            resolveDefinition: $resolveClosure,
            validateRecord: $validateRecord,
        );
    }

    // -------------------------------------------------------------------------
    // A5 — hasMany child failing validation → ValidationException with prefixed key
    // -------------------------------------------------------------------------

    /**
     * @test
     * resolveOnStore with embedded hasMany where one child fails validateRecord
     * throws ValidationException with key '<relation>.0.<childField>'.
     */
    public function test_resolve_on_store_hasMany_child_validation_failure_throws_with_prefixed_key(): void
    {
        $validateRecord = function (EntityDefinitionContract $def, array $attrs): void {
            if (empty($attrs['title'])) {
                throw new ValidationException(['title' => ['The title field is required.']]);
            }
        };

        $field = HasManyField::make('histories', 'App\\Models\\History')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);

        $useCase = $this->makeUseCaseWithValidation($validateRecord);

        // First child has no 'title' (should fail), second is valid.
        $attributes = [
            'histories' => [
                ['title' => ''],         // invalid — missing required title
                ['title' => 'Valid'],    // valid
            ],
        ];

        try {
            $useCase->resolveOnStore($definition, $attributes);
            self::fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            // Must have key 'histories.0.title' (relation.index.childField).
            self::assertArrayHasKey(
                'histories.0.title',
                $e->errors(),
                'ValidationException must carry dot-notation key: <relation>.<index>.<childField>',
            );
            // Must NOT include key for the valid second child.
            self::assertArrayNotHasKey('histories.1.title', $e->errors());
        }
    }

    /**
     * @test
     * resolveOnStore with embedded hasMany where ALL children pass validation does NOT throw.
     */
    public function test_resolve_on_store_hasMany_all_children_valid_does_not_throw(): void
    {
        $validateRecord = function (EntityDefinitionContract $def, array $attrs): void {
            // All pass — no exception.
        };

        $field = HasManyField::make('histories', 'App\\Models\\History')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCaseWithValidation($validateRecord);

        // Attributes for histories array are kept intact for RelationWriter.
        $attributes = [
            'histories' => [
                ['title' => 'Chapter 1'],
                ['title' => 'Chapter 2'],
            ],
        ];

        $result = $useCase->resolveOnStore($definition, $attributes);

        // The hasMany key must remain in attributes (passed through to RelationWriter).
        self::assertArrayHasKey('histories', $result['attributes']);
        // No exception thrown — test passes.
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // A5 — belongsTo child create error re-thrown with prefixed key
    // -------------------------------------------------------------------------

    /**
     * @test
     * resolveOnStore with embedded belongsTo where createRecord throws ValidationException
     * re-throws with key '<fk>.<childField>' (e.g. 'publication_id.title').
     */
    public function test_resolve_on_store_belongsTo_create_exception_rethrown_with_prefixed_key(): void
    {
        $validateRecord = function (EntityDefinitionContract $def, array $attrs): void {
            // Not called for belongsTo — createRecord is called directly.
        };

        $field = BelongsToField::make('publication_id', 'App\\Models\\Publication')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);

        // createReturn throws a ValidationException for a child field.
        $createReturn = function (EntityDefinitionContract $def, EntityRecordContract $rec): EntityRecordContract {
            throw new ValidationException(['title' => ['The title field is required.']]);
        };

        $useCase = $this->makeUseCaseWithValidation($validateRecord, $createReturn);

        try {
            $useCase->resolveOnStore($definition, ['publication_id' => ['author' => 'Alice']]);
            self::fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            // Must have key 'publication_id.title' (fk.childField prefix).
            self::assertArrayHasKey(
                'publication_id.title',
                $e->errors(),
                'ValidationException must carry dot-notation key: <fk>.<childField>',
            );
            // Original un-prefixed key must NOT be present.
            self::assertArrayNotHasKey('title', $e->errors());
        }
    }
}
