<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Action\ExecuteActionUseCase;
use BlackParadise\CoreAdmin\Domain\Action\ActionContext;
use BlackParadise\CoreAdmin\Domain\Action\ActionOutcome;
use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionResolverContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\DomainEventContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Events\ActionExecuted;
use BlackParadise\CoreAdmin\Domain\Exceptions\ActionNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\InvalidActionScopeException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use Closure;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Doubles\Dyn;
use Tests\Doubles\InMemoryValidationProvider;
use Tests\Doubles\InMemoryWriteScope;
use Tests\Doubles\RecordingEventDispatcher;

/**
 * Pins ExecuteActionUseCase (A36, A37, A39).
 *
 * Pinned API: named ctor args actionResolver (ActionResolverContract), authorizationProvider,
 * validator (ValidationProviderContract), localeProvider, writeScope, dispatcher.
 * execute(EntityDefinitionContract $definition, string $actionName, ActionContext $context): ActionResult.
 * Flow: find action by name() among definition->actions() class-strings (resolved via actionResolver)
 * else ActionNotFoundException -> authorize ONCE with ability (permission() ?? "action.<name>") ->
 * scope check against scopes() (InvalidActionScopeException) -> validate context->input against fields()
 * -> handle() inside writeScope -> ActionExecuted(entity, action, scope, keys, result) after commit.
 * Actions here are test doubles that add handle()/scopes()/fields() on top of the current ActionContract.
 */
final class ExecuteActionUseCaseTest extends TestCase
{
    private EntityDefinitionContract $definition;
    private InMemoryWriteScope $scope;
    private RecordingEventDispatcher $events;
    private InMemoryValidationProvider $validator;

    /** @var array<string, ActionContract> */
    private array $actions = [];

    protected function setUp(): void
    {
        $this->events = new RecordingEventDispatcher();
        $this->scope = (new InMemoryWriteScope())->attach($this->events);
        $this->validator = new InMemoryValidationProvider();
        $this->actions = [];
    }

    /**
     * @param list<ActionScope> $scopes
     * @param list<FieldContract> $fields
     * @param Closure(ActionContext): ActionResult $handle
     */
    private function registerAction(
        string $name,
        array $scopes,
        ?string $permission,
        array $fields,
        Closure $handle,
    ): void {
        $action = new class ($name, $scopes, $permission, $fields, $handle) implements ActionContract {
            /**
             * @param list<ActionScope> $scopes
             * @param list<FieldContract> $fields
             */
            public function __construct(
                private readonly string $name,
                private readonly array $scopes,
                private readonly ?string $permission,
                private readonly array $fields,
                private readonly Closure $handle,
            ) {}

            public function name(): string
            {
                return $this->name;
            }

            public function label(): string
            {
                return $this->name;
            }

            /** @return list<ActionScope> */
            public function scopes(): array
            {
                return $this->scopes;
            }

            public function permission(): ?string
            {
                return $this->permission;
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

            /** @return list<FieldContract> */
            public function fields(): array
            {
                return $this->fields;
            }

            public function handle(ActionContext $context): ActionResult
            {
                return ($this->handle)($context);
            }
        };

        $this->actions[$action::class] = $action;
    }

    private function buildDefinition(): EntityDefinitionContract
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('name')->willReturn('stub');
        $definition->method('keyField')->willReturn('id');
        $definition->method('keyType')->willReturn('int');
        $definition->method('fields')->willReturn([]);
        $definition->method('actions')->willReturn(array_keys($this->actions));

        return $definition;
    }

    private function makeUseCase(AuthorizationProviderContract $auth): object
    {
        $resolver = $this->createMock(ActionResolverContract::class);
        $resolver->method('resolve')->willReturnCallback(
            fn(string $class): ActionContract => $this->actions[$class],
        );
        $locale = $this->createMock(LocaleProviderContract::class);
        $locale->method('availableLocales')->willReturn(['en']);
        $locale->method('defaultLocale')->willReturn('en');
        $locale->method('currentLocale')->willReturn('en');
        $this->definition = $this->buildDefinition();

        return Dyn::make(ExecuteActionUseCase::class, [
            'actionResolver' => $resolver,
            'authorizationProvider' => $auth,
            'validator' => $this->validator,
            'localeProvider' => $locale,
            'writeScope' => $this->scope,
            'dispatcher' => $this->events,
        ]);
    }

    private function allowingAuth(): AuthorizationProviderContract
    {
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->method('can')->willReturn(true);

        return $auth;
    }

    /**
     * @param list<EntityKey> $keys
     * @param array<string, mixed> $input
     */
    private function ctx(ActionScope $scope = ActionScope::Row, array $keys = [], array $input = []): ActionContext
    {
        return new ActionContext($this->definition, $scope, $keys, $input);
    }

    public function test_execute_when_action_unknown_throws_action_not_found_without_authorizing(): void
    {
        $this->registerAction('archive', [ActionScope::Row], null, [], static fn(): ActionResult => ActionResult::message('ok'));
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->expects(self::never())->method('can');
        $useCase = $this->makeUseCase($auth);

        $this->expectException(ActionNotFoundException::class);

        Dyn::call($useCase, 'execute', $this->definition, 'missing', $this->ctx());
    }

    public function test_execute_authorizes_exactly_once_with_default_action_permission(): void
    {
        $this->registerAction('archive', [ActionScope::Row], null, [], static fn(): ActionResult => ActionResult::message('ok'));
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->expects(self::once())->method('can')->with('action.archive', self::anything())->willReturn(true);
        $useCase = $this->makeUseCase($auth);

        Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Row, [new EntityKey(1)]));
    }

    public function test_execute_authorizes_with_custom_permission_when_action_declares_one(): void
    {
        $this->registerAction('archive', [ActionScope::Row], 'posts.archive', [], static fn(): ActionResult => ActionResult::message('ok'));
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->expects(self::once())->method('can')->with('posts.archive', self::anything())->willReturn(true);
        $useCase = $this->makeUseCase($auth);

        Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Row, [new EntityKey(1)]));
    }

    public function test_execute_when_unauthorized_throws_and_never_validates_or_handles(): void
    {
        $handled = false;
        $this->registerAction('archive', [ActionScope::Row], null, [], static function () use (&$handled): ActionResult {
            $handled = true;
            return ActionResult::message('ok');
        });
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->method('can')->willReturn(false);
        $useCase = $this->makeUseCase($auth);

        try {
            Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx());
            self::fail('Expected UnauthorizedException.');
        } catch (UnauthorizedException) {
            self::assertFalse($handled);
            self::assertSame(0, $this->scope->runCalls);
            self::assertSame(0, $this->validator->validateCalls);
        }
    }

    public function test_execute_when_unauthorized_and_scope_invalid_reports_unauthorized_first(): void
    {
        $this->registerAction('archive', [ActionScope::Row], null, [], static fn(): ActionResult => ActionResult::message('ok'));
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->method('can')->willReturn(false);
        $useCase = $this->makeUseCase($auth);

        $this->expectException(UnauthorizedException::class);

        Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Bulk));
    }

    public function test_execute_when_scope_not_supported_throws_invalid_action_scope(): void
    {
        $handled = false;
        $this->registerAction('archive', [ActionScope::Row], null, [], static function () use (&$handled): ActionResult {
            $handled = true;
            return ActionResult::message('ok');
        });
        $useCase = $this->makeUseCase($this->allowingAuth());

        try {
            Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Bulk));
            self::fail('Expected InvalidActionScopeException.');
        } catch (InvalidActionScopeException) {
            self::assertFalse($handled);
            self::assertSame(0, $this->scope->runCalls);
        }
    }

    public function test_execute_when_input_invalid_propagates_validation_exception_without_handling(): void
    {
        $handled = false;
        $this->registerAction(
            'archive',
            [ActionScope::Row],
            null,
            [TextField::make('reason')->required()],
            static function () use (&$handled): ActionResult {
                $handled = true;
                return ActionResult::message('ok');
            },
        );
        $this->validator->failNext(['reason' => ['required']]);
        $useCase = $this->makeUseCase($this->allowingAuth());

        try {
            Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Row, [new EntityKey(1)], []));
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(['reason' => ['required']], $e->errors());
            self::assertFalse($handled);
            self::assertSame(0, $this->scope->runCalls);
        }
    }

    public function test_execute_validates_input_against_action_fields_rules(): void
    {
        $this->registerAction(
            'archive',
            [ActionScope::Row],
            null,
            [TextField::make('reason')->required()],
            static fn(): ActionResult => ActionResult::message('ok'),
        );
        $useCase = $this->makeUseCase($this->allowingAuth());

        Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Row, [new EntityKey(1)], ['reason' => 'dup']));

        self::assertSame(1, $this->validator->validateCalls);
        self::assertSame(['reason' => 'dup'], $this->validator->lastData);
        self::assertArrayHasKey('reason', $this->validator->lastRules ?? []);
    }

    public function test_execute_runs_handle_inside_write_scope_and_returns_its_result(): void
    {
        $activeDuringHandle = null;
        $expected = ActionResult::message('done', ActionOutcome::Info);
        $this->registerAction(
            'archive',
            [ActionScope::Row],
            null,
            [],
            function () use (&$activeDuringHandle, $expected): ActionResult {
                $activeDuringHandle = $this->scope->active;
                return $expected;
            },
        );
        $useCase = $this->makeUseCase($this->allowingAuth());

        $result = Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Row, [new EntityKey(1)]));

        self::assertTrue($activeDuringHandle);
        self::assertSame($expected, $result);
        self::assertSame(1, $this->scope->commits);
    }

    public function test_execute_passes_the_context_to_handle(): void
    {
        $seen = null;
        $this->registerAction('archive', [ActionScope::Bulk], null, [], static function (ActionContext $c) use (&$seen): ActionResult {
            $seen = $c;
            return ActionResult::message('ok');
        });
        $useCase = $this->makeUseCase($this->allowingAuth());
        $context = $this->ctx(ActionScope::Bulk, [new EntityKey(1), new EntityKey(2)]);

        Dyn::call($useCase, 'execute', $this->definition, 'archive', $context);

        self::assertSame($context, $seen);
    }

    public function test_execute_dispatches_action_executed_after_commit(): void
    {
        $this->registerAction('archive', [ActionScope::Bulk], null, [], static fn(): ActionResult => ActionResult::message('ok'));
        $useCase = $this->makeUseCase($this->allowingAuth());
        $keys = [new EntityKey(1), new EntityKey(2)];

        $result = Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Bulk, $keys));

        self::assertCount(1, $this->events->dispatched);
        $event = $this->events->dispatched[0];
        self::assertSame(ActionExecuted::class, $event::class);
        self::assertInstanceOf(DomainEventContract::class, $event);
        self::assertGreaterThanOrEqual(1, $this->events->commitsAtDelivery[0]);
        self::assertSame('stub', Dyn::prop($event, 'entity'));
        self::assertSame('archive', Dyn::prop($event, 'action'));
        self::assertSame(ActionScope::Bulk, Dyn::prop($event, 'scope'));
        self::assertSame($keys, Dyn::prop($event, 'keys'));
        self::assertSame($result, Dyn::prop($event, 'result'));
    }

    public function test_execute_when_handle_throws_rolls_back_and_dispatches_no_event(): void
    {
        $this->registerAction('archive', [ActionScope::Row], null, [], static function (): ActionResult {
            throw new RuntimeException('boom');
        });
        $useCase = $this->makeUseCase($this->allowingAuth());

        try {
            Dyn::call($useCase, 'execute', $this->definition, 'archive', $this->ctx(ActionScope::Row, [new EntityKey(1)]));
            self::fail('Expected RuntimeException.');
        } catch (RuntimeException) {
            self::assertSame(1, $this->scope->rollbacks);
            self::assertSame(0, $this->scope->commits);
            self::assertSame([], $this->events->dispatched);
        }
    }

    public function test_execute_on_read_only_entity_still_runs_authorized_action(): void
    {
        $this->registerAction('export', [ActionScope::Entity], null, [], static fn(): ActionResult => ActionResult::message('ok'));
        $useCase = $this->makeUseCase($this->allowingAuth());
        $readOnly = $this->createMock(EntityDefinitionContract::class);
        $readOnly->method('name')->willReturn('stub');
        $readOnly->method('keyField')->willReturn('id');
        $readOnly->method('keyType')->willReturn('int');
        $readOnly->method('fields')->willReturn([]);
        $readOnly->method('actions')->willReturn(array_keys($this->actions));
        $readOnly->method('capabilities')->willReturn(EntityCapabilities::readOnly());
        $this->definition = $readOnly;

        $result = Dyn::call($useCase, 'execute', $this->definition, 'export', $this->ctx(ActionScope::Entity));

        self::assertInstanceOf(ActionResult::class, $result);
        self::assertSame('ok', $result->message);
        self::assertSame(1, $this->scope->commits);
    }
}
