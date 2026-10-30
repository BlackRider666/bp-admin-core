<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\CreateRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Events\EntityCreated;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateRecordUseCaseTest extends TestCase
{
    private EntityMutatorInterface $mutator;
    private AuthorizationProviderContract $auth;
    private EntityDefinitionContract $definition;
    private ValidationProviderContract $validator;
    private EventDispatcherContract $dispatcher;
    private LocaleProviderContract $localeProvider;

    protected function setUp(): void
    {
        $this->mutator = $this->createMock(EntityMutatorInterface::class);
        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('capabilities')->willReturn(EntityCapabilities::all());
        $this->validator = $this->createMock(ValidationProviderContract::class);
        $this->dispatcher = $this->createMock(EventDispatcherContract::class);
        // Task 1.2 will add LocaleProviderContract as 6th ctor arg.
        // Injecting it here pre-IMPL causes ArgumentCountError → legitimate RED.
        $this->localeProvider = $this->createMock(LocaleProviderContract::class);
        $this->localeProvider->method('availableLocales')->willReturn(['en', 'uk']);
        $this->localeProvider->method('defaultLocale')->willReturn('en');
        $this->localeProvider->method('currentLocale')->willReturn('en');

        $this->definition->method('name')->willReturn('users');
        $this->definition->method('keyField')->willReturn('id');
        $this->definition->method('keyType')->willReturn('int');
        $this->definition->method('fields')->willReturn([]);
    }

    private function makeUseCase(): CreateRecordUseCase
    {
        return new CreateRecordUseCase(
            $this->mutator,
            $this->auth,
            $this->definition,
            $this->validator,
            $this->dispatcher,
            $this->localeProvider, // 6th arg — ArgumentCountError on 5-arg ctor pre-IMPL → RED
        );
    }

    public function test_execute_when_authorized_and_valid_creates_record_and_dispatches_event(): void
    {
        $inputRecord = new EntityRecord($this->definition, ['name' => 'Alice']);
        $createdRecord = new EntityRecord($this->definition, ['id' => 1, 'name' => 'Alice']);

        $this->auth->method('can')->with('create', $this->definition)->willReturn(true);
        $this->validator->expects(self::once())->method('validate');
        $this->mutator->method('create')->with($inputRecord)->willReturn($createdRecord);
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EntityCreated::class));

        $useCase = $this->makeUseCase();
        $result = $useCase->execute($inputRecord);

        self::assertSame($createdRecord, $result);
    }

    public function test_execute_when_unauthorized_throws_unauthorized_exception(): void
    {
        $this->auth->method('can')->with('create', $this->definition)->willReturn(false);

        $useCase = $this->makeUseCase();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You can't create users");

        $useCase->execute(new EntityRecord($this->definition));
    }

    public function test_execute_when_validation_fails_propagates_validation_exception(): void
    {
        $this->auth->method('can')->willReturn(true);
        $this->validator
            ->method('validate')
            ->willThrowException(new ValidationException(['name' => ['The name is required.']]));

        $useCase = $this->makeUseCase();

        $this->expectException(ValidationException::class);

        $useCase->execute(new EntityRecord($this->definition));
    }

    public function test_execute_when_unauthorized_does_not_call_mutator(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->mutator->expects(self::never())->method('create');

        $useCase = $this->makeUseCase();

        try {
            $useCase->execute(new EntityRecord($this->definition));
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_when_unauthorized_does_not_call_validator(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->validator->expects(self::never())->method('validate');

        $useCase = $this->makeUseCase();

        try {
            $useCase->execute(new EntityRecord($this->definition));
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_when_unauthorized_does_not_dispatch_event(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->dispatcher->expects(self::never())->method('dispatch');

        $useCase = $this->makeUseCase();

        try {
            $useCase->execute(new EntityRecord($this->definition));
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_when_created_record_has_null_id_throws_logic_exception(): void
    {
        // Persistence invariant: a successful mutator->create() MUST return a
        // record with a non-null id. A null id implies the framework adapter
        // is silently dropping the write (e.g. mass-assignment guarded all
        // attributes). Treat as a programmer/configuration error — fail loud
        // rather than emit a phantom EntityCreated event.
        $inputRecord = new EntityRecord($this->definition, ['name' => 'Bob']);
        $createdRecord = new EntityRecord($this->definition, ['name' => 'Bob']); // no id

        $this->auth->method('can')->willReturn(true);
        $this->validator->method('validate');
        $this->mutator->method('create')->willReturn($createdRecord);
        $this->dispatcher->expects(self::never())->method('dispatch');

        $useCase = $this->makeUseCase();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/persistence invariant/i');

        $useCase->execute($inputRecord);
    }

    public function test_execute_dispatches_entity_created_event_with_correct_key_and_payload(): void
    {
        $this->definition->method('keyField')->willReturn('id');
        $inputRecord = new EntityRecord($this->definition, ['name' => 'Carol']);
        $createdRecord = new EntityRecord($this->definition, ['id' => 42, 'name' => 'Carol']);

        $this->auth->method('can')->willReturn(true);
        $this->validator->method('validate');
        $this->mutator->method('create')->willReturn($createdRecord);

        $dispatchedEvent = null;
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (EntityCreated $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            });

        $useCase = $this->makeUseCase();
        $useCase->execute($inputRecord);

        self::assertNotNull($dispatchedEvent);
        self::assertSame(42, $dispatchedEvent->getEntityKey()->value);
        self::assertSame('int', $dispatchedEvent->getEntityKey()->type);
        self::assertSame(['id' => 42, 'name' => 'Carol'], $dispatchedEvent->getPayload());
    }

    // -------------------------------------------------------------------------
    // A4 (use-case level) — LocaleProvider drives locale-aware validation rules
    // -------------------------------------------------------------------------

    /**
     * Pre-IMPL: makeUseCase() throws ArgumentCountError (6th arg, 5-arg ctor) → ERROR → RED.
     * Post-IMPL: use case consumes LocaleProvider; validator receives {'title.en': [...], 'title.uk': [...]}.
     */
    public function test_execute_passes_locale_aware_rules_to_validator_for_translatable_field(): void
    {
        $titleField = TranslatableField::make('title')->required();

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('capabilities')->willReturn(EntityCapabilities::all());
        $definition->method('name')->willReturn('posts');
        $definition->method('keyField')->willReturn('id');
        $definition->method('keyType')->willReturn('int');
        $definition->method('fields')->willReturn([$titleField]);

        $this->auth->method('can')->willReturn(true);

        $capturedRules = null;
        $this->validator
            ->method('validate')
            ->willReturnCallback(function (array $data, array $rules) use (&$capturedRules): void {
                $capturedRules = $rules;
            });

        $createdRecord = new EntityRecord($definition, ['id' => 1]);
        $this->mutator->method('create')->willReturn($createdRecord);
        $this->dispatcher->method('dispatch');

        // Inline ctor — also fails pre-IMPL (ArgumentCountError on 5-arg ctor).
        $useCase = new CreateRecordUseCase(
            $this->mutator,
            $this->auth,
            $definition,
            $this->validator,
            $this->dispatcher,
            $this->localeProvider, // 6th arg — locale provider supplying ['en', 'uk']
        );

        $useCase->execute(new EntityRecord($definition, ['title' => ['en' => 'Hello', 'uk' => 'Привіт']]));

        // Post-IMPL: use case builds RuleBuilder with locales=['en','uk'] from the provider.
        // The validator must receive per-locale dot-keys, not a flat 'title' key.
        self::assertNotNull($capturedRules, 'Validator must be called');
        self::assertArrayHasKey(
            'title.en',
            $capturedRules,
            "LocaleProvider locales must drive locale-aware keys — 'title.en' expected",
        );
        self::assertArrayHasKey(
            'title.uk',
            $capturedRules,
            "LocaleProvider locales must drive locale-aware keys — 'title.uk' expected",
        );
        self::assertArrayNotHasKey(
            'title',
            $capturedRules,
            "Flat 'title' key must not appear when LocaleProvider supplies locales",
        );
    }

    /**
     * @return array{0: CreateRecordUseCase, 1: EntityDefinitionContract, 2: MockObject&EntityMutatorInterface, 3: MockObject&AuthorizationProviderContract}
     */
    private function makeNonCreatableUseCase(bool $enforceCapability, bool $authorized): array
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('capabilities')->willReturn(
            EntityCapabilities::all()->without(Capability::Create),
        );
        $definition->method('name')->willReturn('publications');
        $definition->method('keyField')->willReturn('id');
        $definition->method('keyType')->willReturn('int');
        $definition->method('fields')->willReturn([]);

        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->method('can')->willReturn($authorized);
        $mutator = $this->createMock(EntityMutatorInterface::class);

        $useCase = new CreateRecordUseCase(
            $mutator,
            $auth,
            $definition,
            $this->validator,
            $this->dispatcher,
            $this->localeProvider,
            enforceCapability: $enforceCapability,
        );

        return [$useCase, $definition, $mutator, $auth];
    }

    public function test_execute_on_non_creatable_definition_is_denied_by_default(): void
    {
        [$useCase, $definition, $mutator] = $this->makeNonCreatableUseCase(true, true);
        $mutator->expects(self::never())->method('create');

        $this->expectException(CapabilityDeniedException::class);
        $useCase->execute(new EntityRecord($definition, ['title' => 'x']));
    }

    public function test_execute_without_capability_enforcement_creates_embed_only_child(): void
    {
        [$useCase, $definition, $mutator] = $this->makeNonCreatableUseCase(false, true);
        $created = new EntityRecord($definition, ['id' => 7, 'title' => 'x']);
        $mutator->expects(self::once())->method('create')->willReturn($created);

        $result = $useCase->execute(new EntityRecord($definition, ['title' => 'x']));

        self::assertSame($created, $result);
    }

    public function test_execute_without_capability_enforcement_still_authorizes_first(): void
    {
        [$useCase, $definition, $mutator] = $this->makeNonCreatableUseCase(false, false);
        $mutator->expects(self::never())->method('create');

        $this->expectException(UnauthorizedException::class);
        $useCase->execute(new EntityRecord($definition, ['title' => 'x']));
    }
}
