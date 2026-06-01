<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\ResolveEmbeddedRelationsUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\HasOneField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use DomainException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\StubEmbeddedDefinition;

final class ResolveEmbeddedRelationsUseCaseTest extends TestCase
{
    /**
     * Build a host EntityDefinition stub returning the given fields.
     */
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
     * Build a use case whose closures record their invocations into shared arrays.
     *
     * @param array<int, array{def: EntityDefinitionContract, record: EntityRecordContract}> $createCalls
     * @param array<int, array{def: EntityDefinitionContract, key: EntityKey, record: EntityRecordContract}> $updateCalls
     * @param EntityDefinitionContract|null $embeddedDefStub Definition returned by resolveDefinition closure.
     * @param callable|null $createReturn Custom resolver: fn(EntityRecordContract): EntityRecordContract.
     * @param callable|null $updateReturn Custom resolver: fn(EntityKey, EntityRecordContract): EntityRecordContract.
     */
    private function makeUseCase(
        array &$createCalls,
        array &$updateCalls,
        ?EntityDefinitionContract $embeddedDefStub = null,
        ?callable $createReturn = null,
        ?callable $updateReturn = null,
    ): ResolveEmbeddedRelationsUseCase {
        $embeddedDefStub ??= new StubEmbeddedDefinition();

        $createClosure = function (
            EntityDefinitionContract $def,
            EntityRecordContract $rec,
        ) use (&$createCalls, $createReturn): EntityRecordContract {
            $createCalls[] = ['def' => $def, 'record' => $rec];
            if ($createReturn !== null) {
                return $createReturn($rec);
            }
            return new EntityRecord($def, ['id' => 42] + $rec->attributes());
        };

        $updateClosure = function (
            EntityDefinitionContract $def,
            EntityKey $key,
            EntityRecordContract $rec,
        ) use (&$updateCalls, $updateReturn): EntityRecordContract {
            $updateCalls[] = ['def' => $def, 'key' => $key, 'record' => $rec];
            if ($updateReturn !== null) {
                return $updateReturn($key, $rec);
            }
            return new EntityRecord($def, [(string) $key->value => $key->value] + $rec->attributes());
        };

        $resolveDefinitionClosure = (fn(string $defClass): EntityDefinitionContract => $embeddedDefStub);

        return new ResolveEmbeddedRelationsUseCase(
            createRecord: $createClosure,
            updateRecord: $updateClosure,
            resolveDefinition: $resolveDefinitionClosure,
        );
    }

    public function test_belongs_to_embed_creates_child_first_and_substitutes_fk_id(): void
    {
        $createCalls = [];
        $updateCalls = [];
        $embeddedDef = new StubEmbeddedDefinition();

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase(
            $createCalls,
            $updateCalls,
            embeddedDefStub: $embeddedDef,
            createReturn: fn($rec): EntityRecord => new EntityRecord($embeddedDef, ['id' => 42, 'name' => 'Bob']),
        );

        $result = $useCase->resolveOnStore($definition, ['author_id' => ['name' => 'Bob']]);

        self::assertSame(['author_id' => 42], $result['attributes']);
        self::assertSame([], $result['defer']);
        self::assertCount(1, $createCalls);
        self::assertSame($embeddedDef, $createCalls[0]['def']);
        self::assertSame(['name' => 'Bob'], $createCalls[0]['record']->attributes());
        self::assertCount(0, $updateCalls);
    }

    public function test_has_one_embed_payload_is_deferred_for_post_host_creation(): void
    {
        $createCalls = [];
        $updateCalls = [];

        $field = HasOneField::make('profile', 'App\\Models\\Profile')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase($createCalls, $updateCalls);

        $result = $useCase->resolveOnStore($definition, ['profile' => ['bio' => 'hello']]);

        self::assertArrayNotHasKey('profile', $result['attributes']);
        self::assertArrayHasKey('profile', $result['defer']);
        self::assertSame($field, $result['defer']['profile']['field']);
        self::assertSame(['bio' => 'hello'], $result['defer']['profile']['payload']);
        self::assertCount(0, $createCalls);
        self::assertCount(0, $updateCalls);
    }

    public function test_owned_relation_requires_embed_payload_key_has_i18n_sentinel(): void
    {
        self::assertStringStartsWith(
            ResolveEmbeddedRelationsUseCase::I18N_SENTINEL,
            ResolveEmbeddedRelationsUseCase::OWNED_RELATION_REQUIRES_EMBED_PAYLOAD_KEY,
            'The constant must begin with the i18n: sentinel so the controller can detect it.',
        );
        self::assertStringEndsWith(
            'bpadmin::validation.owned_relation_requires_embed_payload',
            ResolveEmbeddedRelationsUseCase::OWNED_RELATION_REQUIRES_EMBED_PAYLOAD_KEY,
            'The constant must retain the original translation key suffix.',
        );
    }

    public function test_owned_relation_with_scalar_value_throws_validation_exception(): void
    {
        $createCalls = [];
        $updateCalls = [];

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class)
            ->owns();

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase($createCalls, $updateCalls);

        try {
            $useCase->resolveOnStore($definition, ['author_id' => 99]);
            self::fail('Expected ValidationException to be thrown.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('author_id', $e->errors());
            self::assertSame(
                [ResolveEmbeddedRelationsUseCase::OWNED_RELATION_REQUIRES_EMBED_PAYLOAD_KEY],
                $e->errors()['author_id'],
            );
            // The emitted key must carry the sentinel so the controller's translateErrorKeys
            // can identify and strip it before calling __().
            self::assertStringStartsWith(
                ResolveEmbeddedRelationsUseCase::I18N_SENTINEL,
                $e->errors()['author_id'][0],
            );
        }
        self::assertCount(0, $createCalls);
    }

    public function test_non_embedded_fields_pass_through_untouched(): void
    {
        $createCalls = [];
        $updateCalls = [];

        $definition = $this->definitionWithFields([TextField::make('title')]);
        $useCase = $this->makeUseCase($createCalls, $updateCalls);

        $result = $useCase->resolveOnStore($definition, ['title' => 'foo']);

        self::assertSame(['title' => 'foo'], $result['attributes']);
        self::assertSame([], $result['defer']);
        self::assertCount(0, $createCalls);
        self::assertCount(0, $updateCalls);
    }

    public function test_unknown_field_names_pass_through_untouched(): void
    {
        $createCalls = [];
        $updateCalls = [];

        $definition = $this->definitionWithFields([]);
        $useCase = $this->makeUseCase($createCalls, $updateCalls);

        $result = $useCase->resolveOnStore($definition, ['random_key' => 'val']);

        self::assertSame(['random_key' => 'val'], $result['attributes']);
        self::assertSame([], $result['defer']);
    }

    public function test_resolve_on_update_belongs_to_existing_fk_calls_update_record(): void
    {
        $createCalls = [];
        $updateCalls = [];
        $embeddedDef = new StubEmbeddedDefinition();

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase(
            $createCalls,
            $updateCalls,
            embeddedDefStub: $embeddedDef,
            updateReturn: fn($key, $rec): EntityRecord => new EntityRecord($embeddedDef, ['id' => $key->value]),
        );

        $currentHost = new EntityRecord($definition, ['id' => 1, 'author_id' => 5]);

        $result = $useCase->resolveOnUpdate(
            $definition,
            $currentHost,
            ['author_id' => ['name' => 'Charlie']],
        );

        self::assertSame(['author_id' => 5], $result['attributes']);
        self::assertSame([], $result['defer']);
        self::assertCount(1, $updateCalls);
        self::assertSame($embeddedDef, $updateCalls[0]['def']);
        self::assertSame('5', $updateCalls[0]['key']->value); // EntityKey wraps with (string) cast
        self::assertSame('int', $updateCalls[0]['key']->type);
        self::assertSame(['name' => 'Charlie'], $updateCalls[0]['record']->attributes());
        self::assertCount(0, $createCalls);
    }

    public function test_resolve_on_update_belongs_to_no_existing_fk_calls_create(): void
    {
        $createCalls = [];
        $updateCalls = [];
        $embeddedDef = new StubEmbeddedDefinition();

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase(
            $createCalls,
            $updateCalls,
            embeddedDefStub: $embeddedDef,
            createReturn: fn($rec): EntityRecord => new EntityRecord($embeddedDef, ['id' => 99, 'name' => 'New Author']),
        );

        $currentHost = new EntityRecord($definition, ['id' => 1, 'author_id' => null]);

        $result = $useCase->resolveOnUpdate(
            $definition,
            $currentHost,
            ['author_id' => ['name' => 'New Author']],
        );

        self::assertSame(['author_id' => 99], $result['attributes']);
        self::assertSame([], $result['defer']);
        self::assertCount(1, $createCalls);
        self::assertSame($embeddedDef, $createCalls[0]['def']);
        self::assertCount(0, $updateCalls);
    }

    public function test_resolve_on_update_owned_scalar_throws(): void
    {
        $createCalls = [];
        $updateCalls = [];

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class)
            ->owns();

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase($createCalls, $updateCalls);

        $currentHost = new EntityRecord($definition, ['id' => 1, 'author_id' => 5]);

        try {
            $useCase->resolveOnUpdate($definition, $currentHost, ['author_id' => 88]);
            self::fail('Expected ValidationException to be thrown.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('author_id', $e->errors());
            self::assertSame(
                [ResolveEmbeddedRelationsUseCase::OWNED_RELATION_REQUIRES_EMBED_PAYLOAD_KEY],
                $e->errors()['author_id'],
            );
            self::assertStringStartsWith(
                ResolveEmbeddedRelationsUseCase::I18N_SENTINEL,
                $e->errors()['author_id'][0],
            );
        }
        self::assertCount(0, $createCalls);
        self::assertCount(0, $updateCalls);
    }

    public function test_resolve_on_update_belongs_to_partial_load_missing_fk_throws_domain_exception(): void
    {
        // When the host record was fetched without the FK column (partial-load
        // projection), get($name) returns null and a bare check would silently
        // CREATE a brand-new related record — orphaning the original child.
        // The use case must refuse and require the caller to hydrate the FK.
        $createCalls = [];
        $updateCalls = [];

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase($createCalls, $updateCalls);

        // Host built without 'author_id' at all (simulates partial-load).
        $currentHost = new EntityRecord($definition, ['id' => 1]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/partial-load not supported/i');

        try {
            $useCase->resolveOnUpdate(
                $definition,
                $currentHost,
                ['author_id' => ['name' => 'X']],
            );
        } finally {
            self::assertCount(0, $createCalls);
            self::assertCount(0, $updateCalls);
        }
    }

    public function test_resolve_on_update_has_one_no_existing_child_defers_payload(): void
    {
        $createCalls = [];
        $updateCalls = [];

        $field = HasOneField::make('profile', 'App\\Models\\Profile')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase($createCalls, $updateCalls);

        // Host has no existing profile (relation returns null)
        $currentHost = $this->createMock(EntityRecordContract::class);
        $currentHost->method('relation')->with('profile')->willReturn(null);

        $result = $useCase->resolveOnUpdate(
            $definition,
            $currentHost,
            ['profile' => ['bio' => 'new bio']],
        );

        // Attributes must NOT contain the 'profile' key (stripped from host attrs)
        self::assertArrayNotHasKey('profile', $result['attributes']);

        // Defer map must contain the profile payload for post-update creation
        self::assertArrayHasKey('profile', $result['defer']);
        self::assertSame($field, $result['defer']['profile']['field']);
        self::assertSame(['bio' => 'new bio'], $result['defer']['profile']['payload']);

        // Neither createRecord nor updateRecord should be called yet — deferred
        self::assertCount(0, $createCalls);
        self::assertCount(0, $updateCalls);
    }

    public function test_resolve_on_update_has_one_with_existing_child_calls_update_and_returns_structured(): void
    {
        $createCalls = [];
        $updateCalls = [];
        $embeddedDef = new StubEmbeddedDefinition();

        $field = HasOneField::make('profile', 'App\\Models\\Profile')
            ->embed(StubEmbeddedDefinition::class);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase(
            $createCalls,
            $updateCalls,
            embeddedDefStub: $embeddedDef,
            updateReturn: fn($key, $rec): EntityRecord => new EntityRecord($embeddedDef, ['id' => $key->value]),
        );

        // Host has existing profile with id=7
        $currentHost = $this->createMock(EntityRecordContract::class);
        $currentHost->method('relation')->with('profile')->willReturn(['id' => 7, 'bio' => 'old']);

        $result = $useCase->resolveOnUpdate(
            $definition,
            $currentHost,
            ['profile' => ['bio' => 'updated bio']],
        );

        // Attributes must NOT contain 'profile' (stripped from host attrs)
        self::assertArrayNotHasKey('profile', $result['attributes']);

        // Defer map must be empty (update was called eagerly, no deferred create)
        self::assertSame([], $result['defer']);

        // updateRecord must have been called immediately
        self::assertCount(1, $updateCalls);
        self::assertSame($embeddedDef, $updateCalls[0]['def']);
        self::assertSame('7', $updateCalls[0]['key']->value);
        self::assertSame('int', $updateCalls[0]['key']->type);
        self::assertSame(['bio' => 'updated bio'], $updateCalls[0]['record']->attributes());
        self::assertCount(0, $createCalls);
    }

    public function test_resolve_on_store_with_state_merges_fixed_attributes_into_payload(): void
    {
        $createCalls = [];
        $updateCalls = [];
        $embeddedDef = new StubEmbeddedDefinition();

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class)
            ->withState(['type' => 2]);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase(
            $createCalls,
            $updateCalls,
            embeddedDefStub: $embeddedDef,
            createReturn: fn($rec): EntityRecord => new EntityRecord($embeddedDef, ['id' => 42] + $rec->attributes()),
        );

        $result = $useCase->resolveOnStore($definition, ['author_id' => ['name' => 'Bob']]);

        // The FK substitution still happens
        self::assertSame(['author_id' => 42], $result['attributes']);
        self::assertCount(1, $createCalls);
        // State key 'type' => 2 must be present in the record passed to createRecord
        self::assertArrayHasKey('type', $createCalls[0]['record']->attributes());
        self::assertSame(2, $createCalls[0]['record']->attributes()['type']);
        // Original payload key preserved as well
        self::assertArrayHasKey('name', $createCalls[0]['record']->attributes());
        self::assertSame('Bob', $createCalls[0]['record']->attributes()['name']);
    }

    public function test_resolve_on_store_state_overrides_conflicting_payload_key(): void
    {
        $createCalls = [];
        $updateCalls = [];
        $embeddedDef = new StubEmbeddedDefinition();

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class)
            ->withState(['type' => 2]);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase(
            $createCalls,
            $updateCalls,
            embeddedDefStub: $embeddedDef,
            createReturn: fn($rec): EntityRecord => new EntityRecord($embeddedDef, ['id' => 42] + $rec->attributes()),
        );

        // Payload submits type=99 — state must win with type=2
        $useCase->resolveOnStore($definition, ['author_id' => ['name' => 'Bob', 'type' => 99]]);

        self::assertCount(1, $createCalls);
        self::assertSame(2, $createCalls[0]['record']->attributes()['type'],
            'State value must override a conflicting payload value.');
    }

    public function test_resolve_on_update_with_state_merges_fixed_attributes_into_payload(): void
    {
        $createCalls = [];
        $updateCalls = [];
        $embeddedDef = new StubEmbeddedDefinition();

        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class)
            ->withState(['type' => 2]);

        $definition = $this->definitionWithFields([$field]);
        $useCase = $this->makeUseCase(
            $createCalls,
            $updateCalls,
            embeddedDefStub: $embeddedDef,
            updateReturn: fn($key, $rec): EntityRecord => new EntityRecord($embeddedDef, ['id' => $key->value]),
        );

        $currentHost = new EntityRecord($definition, ['id' => 1, 'author_id' => 5]);

        $useCase->resolveOnUpdate(
            $definition,
            $currentHost,
            ['author_id' => ['name' => 'Charlie']],
        );

        self::assertCount(1, $updateCalls);
        // State key 'type' => 2 must be present in the record passed to updateRecord
        self::assertArrayHasKey('type', $updateCalls[0]['record']->attributes());
        self::assertSame(2, $updateCalls[0]['record']->attributes()['type']);
    }
}
