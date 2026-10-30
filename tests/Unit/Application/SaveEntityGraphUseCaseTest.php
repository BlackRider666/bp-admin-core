<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\CreateRecordUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\ResolveEmbeddedRelationsUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\SaveEntityGraphUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\UpdateRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Commands\SaveEntityGraphCommand;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\EmbeddedChildWriterContract;
use BlackParadise\CoreAdmin\Domain\Contracts\UseCases\SaveEntityGraphUseCaseContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Events\EntityCreated;
use BlackParadise\CoreAdmin\Domain\Events\EntityUpdated;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use LogicException;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\Dyn;
use Tests\Doubles\InMemoryAuthorizationProvider;
use Tests\Doubles\InMemoryEntityMutator;
use Tests\Doubles\InMemoryEntityRepository;
use Tests\Doubles\InMemoryValidationProvider;
use Tests\Doubles\InMemoryWriteScope;
use Tests\Doubles\RecordingEventDispatcher;
use Tests\Fixtures\StubEntityDefinition;

/**
 * Pins the SaveEntityGraphUseCase contract (A22, A23, A24).
 *
 * Pinned API:
 *  - Domain\Commands\SaveEntityGraphCommand(definition, ?key = null, attributes = []) final readonly, public props.
 *  - SaveEntityGraphUseCase named ctor args: authorizationProvider, writeScope, repository,
 *    embeddedResolver (ResolveEmbeddedRelationsUseCase), embeddedWriter (EmbeddedChildWriterContract),
 *    createRecord / updateRecord (Closure(EntityDefinitionContract): Create|UpdateRecordUseCase).
 *  - execute(SaveEntityGraphCommand): EntityRecordContract. key === null => create (ability "create"),
 *    otherwise update (ability "update"). embeddedWriter->writeAll() is called once per successful host save.
 *  - Implements Domain\Contracts\UseCases\SaveEntityGraphUseCaseContract.
 */
final class SaveEntityGraphUseCaseTest extends TestCase
{
    private EntityDefinitionContract $definition;
    private InMemoryAuthorizationProvider $auth;
    private InMemoryWriteScope $scope;
    private RecordingEventDispatcher $events;
    private InMemoryValidationProvider $validator;
    private InMemoryEntityMutator $mutator;
    private InMemoryEntityRepository $repository;

    protected function setUp(): void
    {
        $this->definition = new StubEntityDefinition();
        $this->auth = new InMemoryAuthorizationProvider();
        $this->events = new RecordingEventDispatcher();
        $this->scope = (new InMemoryWriteScope())->attach($this->events);
        $this->validator = new InMemoryValidationProvider();
        $this->mutator = new InMemoryEntityMutator();
        $this->repository = new InMemoryEntityRepository();
    }

    private function locale(): LocaleProviderContract
    {
        $locale = $this->createMock(LocaleProviderContract::class);
        $locale->method('availableLocales')->willReturn(['en']);
        $locale->method('defaultLocale')->willReturn('en');
        $locale->method('currentLocale')->willReturn('en');

        return $locale;
    }

    private function makeUseCase(
        ?EmbeddedChildWriterContract $writer = null,
        ?EntityRepositoryInterface $repository = null,
        ?EntityMutatorInterface $mutator = null,
    ): object {
        $mutator ??= $this->mutator;
        $locale = $this->locale();
        $fail = static function (): never {
            throw new LogicException('Embedded closures must not be used: definition has no embedded fields.');
        };

        return Dyn::make(SaveEntityGraphUseCase::class, [
            'authorizationProvider' => $this->auth,
            'writeScope' => $this->scope,
            'repository' => $repository ?? $this->repository,
            'embeddedResolver' => new ResolveEmbeddedRelationsUseCase($fail, $fail, $fail),
            'embeddedWriter' => $writer ?? $this->createMock(EmbeddedChildWriterContract::class),
            'createRecord' => fn(EntityDefinitionContract $def): CreateRecordUseCase => new CreateRecordUseCase(
                $mutator,
                $this->auth,
                $def,
                $this->validator,
                $this->events,
                $locale,
            ),
            'updateRecord' => fn(EntityDefinitionContract $def): UpdateRecordUseCase => new UpdateRecordUseCase(
                $mutator,
                $this->auth,
                $def,
                $this->validator,
                $this->events,
                $locale,
            ),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function command(EntityDefinitionContract $definition, ?EntityKey $key, array $attributes): object
    {
        return Dyn::make(SaveEntityGraphCommand::class, [$definition, $key, $attributes]);
    }

    private function seedHost(int $id): void
    {
        $this->repository->seed($this->definition, new EntityRecord($this->definition, ['id' => $id, 'name' => 'old']));
    }

    public function test_command_exposes_definition_key_and_attributes(): void
    {
        $key = new EntityKey(3, 'int');

        $command = $this->command($this->definition, $key, ['name' => 'x']);

        self::assertSame($this->definition, Dyn::prop($command, 'definition'));
        self::assertSame($key, Dyn::prop($command, 'key'));
        self::assertSame(['name' => 'x'], Dyn::prop($command, 'attributes'));
    }

    public function test_use_case_implements_save_entity_graph_contract(): void
    {
        self::assertTrue(Dyn::implements(SaveEntityGraphUseCase::class, SaveEntityGraphUseCaseContract::class));
    }

    public function test_contract_declares_single_public_execute(): void
    {
        self::assertSame(['execute'], get_class_methods(SaveEntityGraphUseCaseContract::class));
    }

    public function test_execute_when_creating_unauthorized_throws_before_any_scope_or_write(): void
    {
        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository->expects(self::never())->method('find');
        $mutator = $this->createMock(EntityMutatorInterface::class);
        $mutator->expects(self::never())->method('create');
        $writer = $this->createMock(EmbeddedChildWriterContract::class);
        $writer->expects(self::never())->method('writeAll');
        $useCase = $this->makeUseCase($writer, $repository, $mutator);

        $this->expectException(UnauthorizedException::class);

        try {
            Dyn::call($useCase, 'execute', $this->command($this->definition, null, ['name' => 'x']));
        } finally {
            self::assertSame(0, $this->scope->runCalls);
            self::assertSame(0, $this->validator->validateCalls);
        }
    }

    public function test_execute_when_updating_unauthorized_throws_before_any_read_or_write(): void
    {
        $this->auth->allow('stub', 'create');
        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository->expects(self::never())->method('find');
        $mutator = $this->createMock(EntityMutatorInterface::class);
        $mutator->expects(self::never())->method('update');
        $useCase = $this->makeUseCase(null, $repository, $mutator);

        $this->expectException(UnauthorizedException::class);

        try {
            Dyn::call($useCase, 'execute', $this->command($this->definition, new EntityKey(1), ['name' => 'x']));
        } finally {
            self::assertSame(0, $this->scope->runCalls);
        }
    }

    public function test_execute_when_creating_persists_host_and_runs_in_one_committed_scope(): void
    {
        $this->auth->allowAll();
        $useCase = $this->makeUseCase();

        $record = Dyn::call($useCase, 'execute', $this->command($this->definition, null, ['name' => 'x']));

        self::assertInstanceOf(EntityRecordContract::class, $record);
        self::assertSame(1, $record->id());
        self::assertSame(1, $this->mutator->createCalls);
        self::assertSame(1, $this->scope->runCalls);
        self::assertSame(1, $this->scope->commits);
    }

    public function test_execute_when_creating_writes_embedded_children_once_for_the_host(): void
    {
        $this->auth->allowAll();
        $writer = $this->createMock(EmbeddedChildWriterContract::class);
        $writer->expects(self::once())
            ->method('writeAll')
            ->with($this->definition, self::callback(static fn($host): bool => $host->id() === 1), []);
        $useCase = $this->makeUseCase($writer);

        Dyn::call($useCase, 'execute', $this->command($this->definition, null, ['name' => 'x']));
    }

    public function test_execute_when_creating_dispatches_entity_created_only_after_commit(): void
    {
        $this->auth->allowAll();
        $useCase = $this->makeUseCase();

        Dyn::call($useCase, 'execute', $this->command($this->definition, null, ['name' => 'x']));

        self::assertCount(1, $this->events->dispatched);
        self::assertInstanceOf(EntityCreated::class, $this->events->dispatched[0]);
        self::assertGreaterThanOrEqual(1, $this->events->commitsAtDelivery[0]);
    }

    public function test_execute_when_updating_loads_host_and_updates_it(): void
    {
        $this->auth->allowAll();
        $this->seedHost(5);
        $useCase = $this->makeUseCase();

        $record = Dyn::call($useCase, 'execute', $this->command($this->definition, new EntityKey(5), ['name' => 'new']));

        self::assertInstanceOf(EntityRecordContract::class, $record);
        self::assertSame(5, $record->id());
        self::assertSame(1, $this->mutator->updateCalls);
        self::assertSame(0, $this->mutator->createCalls);
    }

    public function test_execute_when_updating_dispatches_entity_updated_only_after_commit(): void
    {
        $this->auth->allowAll();
        $this->seedHost(5);
        $useCase = $this->makeUseCase();

        Dyn::call($useCase, 'execute', $this->command($this->definition, new EntityKey(5), ['name' => 'new']));

        self::assertCount(1, $this->events->dispatched);
        self::assertInstanceOf(EntityUpdated::class, $this->events->dispatched[0]);
        self::assertGreaterThanOrEqual(1, $this->events->commitsAtDelivery[0]);
    }

    public function test_execute_when_updating_missing_host_throws_entity_not_found_and_rolls_back(): void
    {
        $this->auth->allowAll();
        $useCase = $this->makeUseCase();

        try {
            Dyn::call($useCase, 'execute', $this->command($this->definition, new EntityKey(99), ['name' => 'x']));
            self::fail('Expected EntityNotFoundException.');
        } catch (EntityNotFoundException) {
            self::assertSame(0, $this->mutator->updateCalls);
            self::assertSame(1, $this->scope->rollbacks);
            self::assertSame([], $this->events->dispatched);
        }
    }

    public function test_execute_when_child_validation_fails_rolls_back_scope_and_dispatches_no_events(): void
    {
        $this->auth->allowAll();
        $writer = $this->createMock(EmbeddedChildWriterContract::class);
        $writer->method('writeAll')->willThrowException(new ValidationException(['children.0.title' => ['required']]));
        $useCase = $this->makeUseCase($writer);

        try {
            Dyn::call($useCase, 'execute', $this->command($this->definition, null, ['name' => 'x']));
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(['children.0.title' => ['required']], $e->errors());
            self::assertSame(1, $this->scope->rollbacks);
            self::assertSame(0, $this->scope->commits);
            self::assertSame([], $this->events->dispatched);
        }
    }

    public function test_execute_when_host_validation_fails_rolls_back_and_dispatches_no_events(): void
    {
        $this->auth->allowAll();
        $this->validator->failNext(['name' => ['required']]);
        $useCase = $this->makeUseCase();

        try {
            Dyn::call($useCase, 'execute', $this->command($this->definition, null, []));
            self::fail('Expected ValidationException.');
        } catch (ValidationException) {
            self::assertSame(0, $this->mutator->createCalls);
            self::assertSame(1, $this->scope->rollbacks);
            self::assertSame([], $this->events->dispatched);
        }
    }
}
