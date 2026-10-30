<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Action\ExecuteActionUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\CreateRecordUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\DeleteEntityGraphUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\DeleteRecordUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\ResolveEmbeddedRelationsUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\SaveEntityGraphUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\UpdateRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Action\ActionContext;
use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Commands\SaveEntityGraphCommand;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionResolverContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\EmbeddedChildWriterContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\OwnedDeletesCollectorContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\Dyn;
use Tests\Doubles\InMemoryAuthorizationProvider;
use Tests\Doubles\InMemoryEntityMutator;
use Tests\Doubles\InMemoryEntityRepository;
use Tests\Doubles\InMemoryValidationProvider;
use Tests\Doubles\InMemoryWriteScope;
use Tests\Doubles\RecordingEventDispatcher;
use Tests\Fixtures\CapabilityStubDefinition;

/**
 * Pins capability enforcement (A29, A30, A39): authorization FIRST (-> UnauthorizedException / 403),
 * THEN the capability (-> CapabilityDeniedException / 404). Actions are never capability-gated.
 * Graph use cases check once, for the host. A denied capability must not touch the mutator.
 *
 * Mapping: create -> Capability::Create, update -> Capability::Update, delete -> Capability::Delete.
 */
final class CapabilityEnforcementTest extends TestCase
{
    private InMemoryAuthorizationProvider $auth;
    private RecordingEventDispatcher $events;
    private InMemoryValidationProvider $validator;
    private InMemoryWriteScope $scope;

    /** @var EntityMutatorInterface&MockObject */
    private EntityMutatorInterface $mutator;

    protected function setUp(): void
    {
        $this->auth = new InMemoryAuthorizationProvider();
        $this->events = new RecordingEventDispatcher();
        $this->validator = new InMemoryValidationProvider();
        $this->scope = (new InMemoryWriteScope())->attach($this->events);
        $this->mutator = $this->createMock(EntityMutatorInterface::class);
    }

    private function locale(): LocaleProviderContract
    {
        $locale = $this->createMock(LocaleProviderContract::class);
        $locale->method('availableLocales')->willReturn(['en']);
        $locale->method('defaultLocale')->willReturn('en');
        $locale->method('currentLocale')->willReturn('en');

        return $locale;
    }

    private function definition(EntityCapabilities $capabilities): CapabilityStubDefinition
    {
        return new CapabilityStubDefinition($capabilities);
    }

    private function createUseCase(EntityDefinitionContract $definition): CreateRecordUseCase
    {
        return new CreateRecordUseCase($this->mutator, $this->auth, $definition, $this->validator, $this->events, $this->locale());
    }

    private function updateUseCase(EntityDefinitionContract $definition): UpdateRecordUseCase
    {
        return new UpdateRecordUseCase($this->mutator, $this->auth, $definition, $this->validator, $this->events, $this->locale());
    }

    private function deleteUseCase(EntityDefinitionContract $definition): DeleteRecordUseCase
    {
        return new DeleteRecordUseCase(new InMemoryEntityRepository(), $this->mutator, $this->auth, $definition, $this->events);
    }

    private function saveGraphUseCase(): SaveEntityGraphUseCase
    {
        $fail = static function (): never {
            throw new LogicException('Embedded closures must not be used.');
        };
        $mutator = new InMemoryEntityMutator();
        $repository = new InMemoryEntityRepository();

        return new SaveEntityGraphUseCase(
            authorizationProvider: $this->auth,
            writeScope: $this->scope,
            repository: $repository,
            embeddedResolver: new ResolveEmbeddedRelationsUseCase($fail, $fail, $fail),
            embeddedWriter: $this->createMock(EmbeddedChildWriterContract::class),
            createRecord: fn(EntityDefinitionContract $def): CreateRecordUseCase => new CreateRecordUseCase(
                $mutator,
                $this->auth,
                $def,
                $this->validator,
                $this->events,
                $this->locale(),
            ),
            updateRecord: fn(EntityDefinitionContract $def): UpdateRecordUseCase => new UpdateRecordUseCase(
                $mutator,
                $this->auth,
                $def,
                $this->validator,
                $this->events,
                $this->locale(),
            ),
        );
    }

    private function deleteGraphUseCase(InMemoryEntityRepository $repository): DeleteEntityGraphUseCase
    {
        $collector = $this->createMock(OwnedDeletesCollectorContract::class);
        $collector->method('collect')->willReturn([]);

        return new DeleteEntityGraphUseCase($this->auth, $this->scope, $repository, $this->mutator, $collector, $this->events);
    }

    private function record(EntityDefinitionContract $definition): EntityRecord
    {
        return new EntityRecord($definition, ['name' => 'x']);
    }

    // ---- CreateRecordUseCase ------------------------------------------------

    public function test_create_when_unauthorized_and_read_only_throws_unauthorized_before_capability(): void
    {
        $definition = $this->definition(EntityCapabilities::readOnly());

        $this->expectException(UnauthorizedException::class);
        $this->createUseCase($definition)->execute($this->record($definition));
    }

    public function test_create_when_authorized_but_create_capability_denied_throws_capability_denied(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Create));

        $this->expectException(CapabilityDeniedException::class);
        $this->createUseCase($definition)->execute($this->record($definition));
    }

    public function test_create_when_capability_denied_does_not_touch_mutator(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::readOnly());
        $this->mutator->expects(self::never())->method('create');

        try {
            $this->createUseCase($definition)->execute($this->record($definition));
        } catch (CapabilityDeniedException) {
            // expected
        }
    }

    public function test_create_when_only_update_capability_denied_still_creates(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Update));
        $this->mutator->expects(self::once())->method('create')->willReturn(new EntityRecord($definition, ['id' => 1]));

        $result = $this->createUseCase($definition)->execute($this->record($definition));

        self::assertSame(1, $result->id());
    }

    // ---- UpdateRecordUseCase ------------------------------------------------

    public function test_update_when_unauthorized_and_read_only_throws_unauthorized_before_capability(): void
    {
        $definition = $this->definition(EntityCapabilities::readOnly());

        $this->expectException(UnauthorizedException::class);
        $this->updateUseCase($definition)->execute(new EntityKey(1, 'int'), $this->record($definition));
    }

    public function test_update_when_authorized_but_update_capability_denied_throws_capability_denied(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Update));

        $this->expectException(CapabilityDeniedException::class);
        $this->updateUseCase($definition)->execute(new EntityKey(1, 'int'), $this->record($definition));
    }

    public function test_update_when_capability_denied_does_not_touch_mutator(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::readOnly());
        $this->mutator->expects(self::never())->method('update');

        try {
            $this->updateUseCase($definition)->execute(new EntityKey(1, 'int'), $this->record($definition));
        } catch (CapabilityDeniedException) {
            // expected
        }
    }

    public function test_update_when_only_delete_capability_denied_still_updates(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Delete));
        $this->mutator->expects(self::once())->method('update')->willReturn(new EntityRecord($definition, ['id' => 1]));

        $result = $this->updateUseCase($definition)->execute(new EntityKey(1, 'int'), $this->record($definition));

        self::assertSame(1, $result->id());
    }

    // ---- DeleteRecordUseCase ------------------------------------------------

    public function test_delete_when_unauthorized_and_read_only_throws_unauthorized_before_capability(): void
    {
        $definition = $this->definition(EntityCapabilities::readOnly());

        $this->expectException(UnauthorizedException::class);
        $this->deleteUseCase($definition)->execute(new EntityKey(1, 'int'));
    }

    public function test_delete_when_authorized_but_delete_capability_denied_throws_capability_denied(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Delete));

        $this->expectException(CapabilityDeniedException::class);
        $this->deleteUseCase($definition)->execute(new EntityKey(1, 'int'));
    }

    public function test_delete_when_capability_denied_does_not_touch_mutator(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::readOnly());
        $this->mutator->expects(self::never())->method('delete');

        try {
            $this->deleteUseCase($definition)->execute(new EntityKey(1, 'int'));
        } catch (CapabilityDeniedException) {
            // expected
        }
    }

    public function test_delete_when_only_create_capability_denied_still_deletes(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Create));
        $this->mutator->expects(self::once())->method('delete')->willReturn(true);

        $result = $this->deleteUseCase($definition)->execute(new EntityKey(1, 'int'));

        self::assertTrue($result);
    }

    // ---- Graph use cases (checked once, for the host) -----------------------

    public function test_save_graph_when_unauthorized_and_read_only_throws_unauthorized_before_capability(): void
    {
        $definition = $this->definition(EntityCapabilities::readOnly());

        $this->expectException(UnauthorizedException::class);
        $this->saveGraphUseCase()->execute(new SaveEntityGraphCommand($definition, null, ['name' => 'x']));
    }

    public function test_save_graph_when_creating_and_create_capability_denied_throws_capability_denied(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Create));

        $this->expectException(CapabilityDeniedException::class);
        $this->saveGraphUseCase()->execute(new SaveEntityGraphCommand($definition, null, ['name' => 'x']));
    }

    public function test_save_graph_when_updating_and_update_capability_denied_throws_capability_denied(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Update));

        $this->expectException(CapabilityDeniedException::class);
        $this->saveGraphUseCase()->execute(new SaveEntityGraphCommand($definition, new EntityKey(1, 'int'), ['name' => 'x']));
    }

    public function test_save_graph_when_capability_denied_dispatches_no_events(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::readOnly());

        try {
            $this->saveGraphUseCase()->execute(new SaveEntityGraphCommand($definition, null, ['name' => 'x']));
        } catch (CapabilityDeniedException) {
            // expected
        }

        self::assertSame([], $this->events->dispatched);
    }

    public function test_delete_graph_when_unauthorized_and_read_only_throws_unauthorized_before_capability(): void
    {
        $definition = $this->definition(EntityCapabilities::readOnly());

        $this->expectException(UnauthorizedException::class);
        $this->deleteGraphUseCase(new InMemoryEntityRepository())->execute($definition, new EntityKey(1, 'int'));
    }

    public function test_delete_graph_when_authorized_but_delete_capability_denied_throws_capability_denied(): void
    {
        $this->auth->allowAll();
        $definition = $this->definition(EntityCapabilities::all()->without(Capability::Delete));
        $repository = (new InMemoryEntityRepository())->seed($definition, new EntityRecord($definition, ['id' => 1]));
        $this->mutator->expects(self::never())->method('delete');

        $this->expectException(CapabilityDeniedException::class);
        $this->deleteGraphUseCase($repository)->execute($definition, new EntityKey(1, 'int'));
    }

    // ---- Actions are NOT capability-gated (A39) -----------------------------

    public function test_execute_action_when_authorized_on_read_only_entity_runs_handler(): void
    {
        $this->auth->allowAll();
        $action = new class implements ActionContract {
            public function name(): string
            {
                return 'ping';
            }

            public function label(): string
            {
                return 'Ping';
            }

            /** @return list<ActionScope> */
            public function scopes(): array
            {
                return [ActionScope::Entity];
            }

            public function permission(): ?string
            {
                return null;
            }

            public function confirm(): bool
            {
                return false;
            }

            /** @return array<string, mixed> */
            public function meta(): array
            {
                return [];
            }

            /** @return list<never> */
            public function fields(): array
            {
                return [];
            }

            public function handle(ActionContext $context): ActionResult
            {
                return ActionResult::message('pong');
            }
        };
        $definition = Dyn::make(CapabilityStubDefinition::class, [EntityCapabilities::readOnly(), [$action::class]]);
        $resolver = $this->createMock(ActionResolverContract::class);
        $resolver->method('resolve')->willReturn($action);
        $useCase = new ExecuteActionUseCase($resolver, $this->auth, $this->validator, $this->locale(), $this->scope, $this->events);

        self::assertInstanceOf(EntityDefinitionContract::class, $definition);
        $result = $useCase->execute($definition, 'ping', new ActionContext($definition, ActionScope::Entity, [], []));

        self::assertSame('pong', $result->message);
    }
}
