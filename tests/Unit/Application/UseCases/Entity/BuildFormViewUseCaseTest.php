<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\BuildFormViewUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\RelationOptionsProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\RelationFieldContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\StubCyclicEmbedDefinitionA;
use Tests\Fixtures\StubEmbeddedDefinition;
use Tests\Fixtures\StubEmbeddedDefinitionWithHostBackRef;
use Tests\Fixtures\StubEmbeddedDefinitionWithRelations;
use Tests\Fixtures\StubSelfReferencingEmbeddedDefinition;

final class BuildFormViewUseCaseTest extends TestCase
{
    private AuthorizationProviderContract $auth;
    private EntityDefinitionContract $definition;

    protected function setUp(): void
    {
        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('name')->willReturn('users');
    }

    public function test_execute_when_authorized_returns_only_form_visible_fields(): void
    {
        $visibleField = TextField::make('name');
        $hiddenField = TextField::make('internal_notes')->hideFromForm();

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$visibleField, $hiddenField]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertCount(1, $result);
        self::assertSame($visibleField, $result[0]);
    }

    public function test_execute_when_authorized_with_all_visible_fields_returns_all(): void
    {
        $field1 = TextField::make('name');
        $field2 = TextField::make('email');
        $field3 = TextField::make('bio');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field1, $field2, $field3]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertCount(3, $result);
    }

    public function test_execute_when_authorized_with_no_visible_fields_returns_empty_array(): void
    {
        $field1 = TextField::make('audit_log')->hideFromForm();
        $field2 = TextField::make('system_flag')->hideFromForm();

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field1, $field2]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertSame([], $result);
    }

    public function test_execute_when_unauthorized_throws_unauthorized_exception(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->definition->method('fields')->willReturn([]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You can't create users");

        $useCase->execute('create');
    }

    public function test_execute_uses_provided_action_for_authorization(): void
    {
        $this->auth
            ->expects(self::once())
            ->method('can')
            ->with('edit', $this->definition)
            ->willReturn(false);

        $this->definition->method('fields')->willReturn([]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);

        try {
            $useCase->execute('edit');
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_result_has_consecutive_integer_keys(): void
    {
        $field1 = TextField::make('a');
        $field2 = TextField::make('b')->hideFromForm();
        $field3 = TextField::make('c');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field1, $field2, $field3]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertSame([0, 1], array_keys($result));
    }

    // -------------------------------------------------------------------------
    // RelationOptionsProviderContract integration
    // -------------------------------------------------------------------------

    public function test_execute_without_provider_non_embedded_relation_has_no_options_in_meta(): void
    {
        // Optional provider — three-arg constructor without provider must work.
        // For NON-embedded relation fields the provider gate still controls OPTIONS:
        // without a provider no 'options' key is injected. (Embedded container fields
        // ALWAYS receive meta['embeddedFields'] regardless of the provider — A8.)
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertCount(1, $result);
        self::assertArrayNotHasKey('options', $result[0]->meta());
    }

    public function test_execute_with_options_provider_decorates_belongs_to_field_meta(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider
            ->expects(self::once())
            ->method('options')
            ->with(self::isInstanceOf(RelationFieldContract::class))
            ->willReturn([
                ['id' => 1, 'label' => 'Alice'],
                ['id' => 2, 'label' => 'Bob'],
            ]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $result = $useCase->execute('create');

        self::assertSame(
            [
                'options' => [
                    ['id' => 1, 'label' => 'Alice'],
                    ['id' => 2, 'label' => 'Bob'],
                ],
            ],
            $result[0]->meta(),
        );
    }

    public function test_execute_with_options_provider_decorates_belongs_to_many_field(): void
    {
        $field = BelongsToManyField::make('roles', 'App\\Models\\Role');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider
            ->expects(self::once())
            ->method('options')
            ->willReturn([['id' => 10, 'label' => 'Editor']]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $result = $useCase->execute('create');

        self::assertSame(
            [['id' => 10, 'label' => 'Editor']],
            $result[0]->meta()['options'] ?? null,
        );
    }

    public function test_execute_with_options_provider_skips_embedded_relation(): void
    {
        // Embedded relations render their nested fieldset via a different
        // presenter path; the options list would be meaningless and the
        // provider call would be wasted I/O.
        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->expects(self::never())->method('options');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $useCase->execute('create');
    }

    public function test_execute_with_options_provider_skips_has_many(): void
    {
        // has_many is a write-side relation rendered as a nested fieldset/list,
        // not a select. Provider must not be queried.
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->expects(self::never())->method('options');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $useCase->execute('create');
    }

    public function test_execute_with_options_provider_skips_non_relation_fields(): void
    {
        $field = TextField::make('title');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->expects(self::never())->method('options');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $useCase->execute('create');
    }

    // -------------------------------------------------------------------------
    // Bug #2 — recursive option decoration inside embedded definitions
    // -------------------------------------------------------------------------

    public function test_execute_with_options_provider_decorates_relation_fields_inside_embedded_definition(): void
    {
        // The outer field is embedded — it renders a nested fieldset.
        // Its embedded definition (StubEmbeddedDefinitionWithRelations) carries
        // a BelongsToField and a BelongsToManyField that MUST be decorated.
        $embeddedField = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithRelations::class);

        // Provider must be called exactly twice — once for city_id (BelongsToField)
        // and once for tags (BelongsToManyField) inside the embedded definition.
        // The container field (address_id) itself must NOT trigger an options call.
        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider
            ->expects(self::exactly(2))
            ->method('options')
            ->willReturn([['id' => 1, 'label' => 'Option']]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$embeddedField]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $result = $useCase->execute('create');

        // The container field must carry meta['embeddedFields'] — the decorated
        // sub-field list that the view consumes WITHOUT re-resolving the definition.
        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? null;
        self::assertIsArray($embeddedFields, 'embedded container must expose decorated sub-fields in meta["embeddedFields"]');
        self::assertNotEmpty($embeddedFields);
    }

    public function test_execute_with_options_provider_does_not_call_options_for_embedded_container_itself(): void
    {
        // The embedded field is a container — options() must NOT be called for it.
        // Only its sub-fields receive options.
        $embeddedField = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithRelations::class);

        $callArgs = [];
        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider
            ->method('options')
            ->willReturnCallback(function (RelationFieldContract $f) use (&$callArgs): array {
                $callArgs[] = $f->name();
                return [];
            });

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$embeddedField]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $useCase->execute('create');

        // 'address_id' is the embedded container — must NOT appear in provider calls.
        self::assertNotContains('address_id', $callArgs);
        // Sub-fields inside the embedded definition MUST appear.
        self::assertContains('city_id', $callArgs);
        self::assertContains('tags', $callArgs);
    }

    public function test_execute_with_options_provider_embedded_sub_fields_receive_options_meta(): void
    {
        // Core correctness check: embedded sub-fields must ACTUALLY carry options
        // in their meta(), not just have options() called on throwaway instances.
        // The use case must attach a decorated sub-field list to the container
        // field's meta['embeddedFields'] so the view reads from there instead of
        // calling new $embeddedDefinitionClass()->fields() (which returns fresh,
        // un-decorated instances and loses all injected options).
        $embeddedField = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithRelations::class);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider
            ->method('options')
            ->willReturn([['id' => 5, 'label' => 'City X']]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$embeddedField]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $result = $useCase->execute('create');

        // The result contains the embedded container field.
        self::assertCount(1, $result);

        // The container field must expose its decorated sub-fields via meta['embeddedFields'].
        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? null;
        self::assertIsArray($embeddedFields, 'meta["embeddedFields"] must be an array on embedded container');

        // Only form-visible sub-fields are included.
        // StubEmbeddedDefinitionWithRelations has: TextField('name'), BelongsToField('city_id'), BelongsToManyField('tags')
        // All three are visibleOnForm() by default.
        self::assertCount(3, $embeddedFields);

        // Find the relation sub-fields by name.
        /** @var array<string, FieldContract> $byName */
        $byName = [];
        foreach ($embeddedFields as $sf) {
            self::assertInstanceOf(FieldContract::class, $sf);
            $byName[$sf->name()] = $sf;
        }

        self::assertArrayHasKey('city_id', $byName, 'city_id sub-field must be in embeddedFields');
        self::assertArrayHasKey('tags', $byName, 'tags sub-field must be in embeddedFields');

        // Relation sub-fields must carry options in their meta — this is the real
        // end-to-end assertion that the previous test missed.
        self::assertSame(
            [['id' => 5, 'label' => 'City X']],
            $byName['city_id']->meta()['options'] ?? null,
            'city_id sub-field must have options injected',
        );
        self::assertSame(
            [['id' => 5, 'label' => 'City X']],
            $byName['tags']->meta()['options'] ?? null,
            'tags sub-field must have options injected',
        );

        // Non-relation sub-field must NOT have options.
        self::assertArrayNotHasKey('options', $byName['name']->meta(), 'TextField must not have options');
    }

    // -------------------------------------------------------------------------
    // H6 — cycle/self-reference guard: no stack overflow on cyclic embed
    // -------------------------------------------------------------------------

    public function test_execute_self_referencing_embed_does_not_overflow(): void
    {
        // StubSelfReferencingEmbeddedDefinition has a BelongsToField that
        // embeds the same definition class. Without the visited guard this
        // triggers infinite recursion and a stack overflow.
        $field = BelongsToField::make('node_id', 'App\\Models\\Node')
            ->embed(StubSelfReferencingEmbeddedDefinition::class);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        // options() may be called for non-cyclic relation fields inside the
        // embedded definition — we just don't care how many times, only that
        // we finish without crashing.
        $provider->method('options')->willReturn([]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);

        // Must complete without throwing or hitting a stack overflow.
        $result = $useCase->execute('create');

        // The top-level field is returned.
        self::assertCount(1, $result);

        // The embedded container must expose a (possibly partial) sub-field list.
        // The cyclic sub-field is skipped; non-relation sub-fields (TextField) remain.
        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? null;
        self::assertIsArray($embeddedFields, 'embedded container must expose sub-fields in meta["embeddedFields"]');
    }

    public function test_execute_mutual_cyclic_embed_does_not_overflow(): void
    {
        // StubCyclicEmbedDefinitionA embeds B, B embeds A — a two-step cycle.
        // Without the visited guard this triggers infinite recursion.
        $field = BelongsToField::make('a_id', 'App\\Models\\A')
            ->embed(StubCyclicEmbedDefinitionA::class);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);

        // Must complete without throwing or hitting a stack overflow.
        $result = $useCase->execute('create');

        self::assertCount(1, $result);

        // The container for A exposes its sub-fields; B's back-reference to A
        // is dropped by the cycle guard, so the recursion stops cleanly.
        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? null;
        self::assertIsArray($embeddedFields, 'embedded container must expose sub-fields even with cyclic definition');
    }

    public function test_execute_cyclic_embed_non_relation_sub_fields_are_still_returned(): void
    {
        // When a cycle is detected and the cyclic sub-tree is skipped,
        // non-cyclic sub-fields at the first level must still be present
        // (i.e. TextField('a_name') inside StubCyclicEmbedDefinitionA).
        $field = BelongsToField::make('a_id', 'App\\Models\\A')
            ->embed(StubCyclicEmbedDefinitionA::class);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $result = $useCase->execute('create');

        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? [];

        // StubCyclicEmbedDefinitionA has: TextField('a_name'), BelongsToField('b_id' embed B)
        // Both are visibleOnForm(). The TextField must survive; B sub-fields
        // that would recurse back to A are cut by the guard.
        $names = array_map(fn($f) => $f->name(), $embeddedFields);
        self::assertContains('a_name', $names, 'non-cyclic sub-fields must still be included');
    }

    // -------------------------------------------------------------------------
    // A7 / A8 — always-emit embeddedFields + host-FK skip (Slice 2 / C5)
    // -------------------------------------------------------------------------

    /**
     * Build a lightweight EntityDefinition from a field list and optional model class.
     * Avoids mock-property PHPStan issues while keeping tests concise.
     *
     * @param list<FieldContract> $fields
     */
    private function makeDefinition(array $fields, string $modelClass = 'App\\Models\\Entity'): EntityDefinitionContract
    {
        return new class ($fields, $modelClass) implements EntityDefinitionContract {
            /**
             * @param list<FieldContract> $fields
             */
            public function __construct(
                private readonly array $fields,
                private readonly string $modelClass,
            ) {}

            public function name(): string
            {
                return 'test';
            }

            public function label(): string
            {
                return 'Test';
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
                return $this->modelClass;
            }

            /** @return list<FieldContract> */
            public function fields(): array
            {
                return $this->fields;
            }

            /** @return list<class-string<ActionContract>> */
            public function actions(): array
            {
                return [];
            }

            public function defaultPerPage(): int
            {
                return 25;
            }

            /** @return list<string> */
            public function searchFields(): array
            {
                return [];
            }

            public function resolveName(): string
            {
                return 'test';
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

    public function test_execute_without_provider_embedded_container_has_embedded_fields_in_meta(): void
    {
        // Slice 2 / A8 invariant: meta['embeddedFields'] must be emitted on an
        // embedded container field even when NO RelationOptionsProvider is injected.
        // Pre-IMPL: the gate is still provider-only → meta is empty → RED.
        $embeddedField = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithRelations::class);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$embeddedField]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertCount(1, $result);

        self::assertArrayHasKey(
            'embeddedFields',
            $result[0]->meta(),
            'embedded container must have meta["embeddedFields"] without a RelationOptionsProvider',
        );
        self::assertIsArray($result[0]->meta()['embeddedFields']);
        // StubEmbeddedDefinitionWithRelations: TextField('name'), BelongsToField('city_id'),
        // BelongsToManyField('tags') — all visibleOnForm() by default → 3 sub-fields.
        self::assertCount(3, $result[0]->meta()['embeddedFields']);
    }

    public function test_execute_a8_embedded_fields_identical_with_and_without_provider(): void
    {
        // A8: the embeddedFields list must be IDENTICAL regardless of whether a
        // RelationOptionsProvider is injected. The provider decorates sub-field
        // OPTIONS only; it must not affect which fields appear in the list.
        // Pre-IMPL: without provider → no embeddedFields → first assertIsArray fails → RED.
        $this->auth->method('can')->willReturn(true);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([]);

        // Fresh field instances for each run — withMeta mutates in place.
        $fieldWith = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithRelations::class);
        $fieldWithout = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithRelations::class);

        $defWith = $this->makeDefinition([$fieldWith]);
        $defWithout = $this->makeDefinition([$fieldWithout]);

        $resultWith = (new BuildFormViewUseCase($this->auth, $defWith, $provider))->execute('create');
        $resultWithout = (new BuildFormViewUseCase($this->auth, $defWithout))->execute('create');

        $metaWith = $resultWith[0]->meta();
        $metaWithout = $resultWithout[0]->meta();

        self::assertIsArray(
            $metaWithout['embeddedFields'] ?? null,
            'embeddedFields must exist without provider (A8)',
        );
        self::assertIsArray(
            $metaWith['embeddedFields'] ?? null,
            'embeddedFields must exist with provider',
        );

        /** @var list<FieldContract> $fieldsWith */
        $fieldsWith = $metaWith['embeddedFields'];
        /** @var list<FieldContract> $fieldsWithout */
        $fieldsWithout = $metaWithout['embeddedFields'];

        $namesWith = array_map(static fn(FieldContract $f): string => $f->name(), $fieldsWith);
        $namesWithout = array_map(static fn(FieldContract $f): string => $f->name(), $fieldsWithout);
        sort($namesWith);
        sort($namesWithout);

        self::assertSame(
            $namesWith,
            $namesWithout,
            'embeddedFields list must be identical with and without provider (A8)',
        );
    }

    public function test_execute_a7_embedded_back_fk_is_absent_from_embedded_fields(): void
    {
        // A7 (negative): a BelongsTo sub-field whose target() === entityDefinition->modelClass()
        // (i.e. pointsBackTo($hostModelClass) is true) must be ABSENT from meta['embeddedFields'].
        // Pre-IMPL: no FK-skip in core → post_id is wrongly present → assertNotContains fails → RED.
        $hostModelClass = 'App\\Models\\Post';

        $embeddedField = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithHostBackRef::class);

        // Use a provider so embeddedFields IS built (tests FK-skip, not the missing-provider path).
        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([]);

        $this->auth->method('can')->willReturn(true);

        $def = $this->makeDefinition([$embeddedField], $hostModelClass);
        $useCase = new BuildFormViewUseCase($this->auth, $def, $provider);
        $result = $useCase->execute('create');

        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? null;
        self::assertIsArray($embeddedFields, 'embedded container must have meta["embeddedFields"]');

        /** @var list<FieldContract> $embeddedFields */
        $names = array_map(static fn(FieldContract $f): string => $f->name(), $embeddedFields);

        self::assertNotContains(
            'post_id',
            $names,
            'back-FK sub-field (BelongsTo pointing at host model) must be filtered out of embeddedFields (A7)',
        );
    }

    public function test_execute_a7_embedded_fk_to_different_model_is_present_in_embedded_fields(): void
    {
        // A7 (positive): a BelongsTo sub-field whose target() !== entityDefinition->modelClass()
        // must remain in meta['embeddedFields']. Only the back-FK is removed.
        // Pre-IMPL: current impl includes all visibleOnForm() sub-fields (no FK-skip), so
        // category_id IS present already → assertContains passes → pre-green.
        $hostModelClass = 'App\\Models\\Post';

        $embeddedField = BelongsToField::make('address_id', 'App\\Models\\Address')
            ->embed(StubEmbeddedDefinitionWithHostBackRef::class);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([]);

        $this->auth->method('can')->willReturn(true);

        $def = $this->makeDefinition([$embeddedField], $hostModelClass);
        $useCase = new BuildFormViewUseCase($this->auth, $def, $provider);
        $result = $useCase->execute('create');

        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? null;
        self::assertIsArray($embeddedFields, 'embedded container must have meta["embeddedFields"]');

        /** @var list<FieldContract> $embeddedFields */
        $names = array_map(static fn(FieldContract $f): string => $f->name(), $embeddedFields);

        self::assertContains(
            'category_id',
            $names,
            'BelongsTo pointing to a different model must remain in embeddedFields (A7 positive)',
        );
    }
}
