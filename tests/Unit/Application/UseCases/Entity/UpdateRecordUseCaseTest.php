<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\UpdateRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Events\EntityUpdated;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateRecordUseCaseTest extends TestCase
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

    private function makeUseCase(): UpdateRecordUseCase
    {
        return new UpdateRecordUseCase(
            $this->mutator,
            $this->auth,
            $this->definition,
            $this->validator,
            $this->dispatcher,
            $this->localeProvider, // 6th arg — ArgumentCountError on 5-arg ctor pre-IMPL → RED
        );
    }

    public function test_execute_when_authorized_and_exists_updates_record_and_dispatches_event(): void
    {
        $key = new EntityKey(1, 'int');
        $inputRecord = new EntityRecord($this->definition, ['name' => 'Alice Updated']);
        $updatedRecord = new EntityRecord($this->definition, ['id' => 1, 'name' => 'Alice Updated']);

        $this->auth->method('can')->with('update', $this->definition)->willReturn(true);
        $this->validator->expects(self::once())->method('validate');
        $this->mutator->method('update')->with($key, $inputRecord)->willReturn($updatedRecord);
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EntityUpdated::class));

        $useCase = $this->makeUseCase();
        $result = $useCase->execute($key, $inputRecord);

        self::assertSame($updatedRecord, $result);
    }

    public function test_execute_when_unauthorized_throws_unauthorized_exception(): void
    {
        $this->auth->method('can')->with('update', $this->definition)->willReturn(false);

        $useCase = $this->makeUseCase();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You can't update users");

        $useCase->execute(new EntityKey(1), new EntityRecord($this->definition));
    }

    public function test_execute_when_record_not_found_throws_entity_not_found_exception(): void
    {
        $key = new EntityKey(999, 'int');

        $this->auth->method('can')->willReturn(true);
        $this->mutator->method('update')->willThrowException(new EntityNotFoundException('users', 999));

        $useCase = $this->makeUseCase();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Entity [users] with key [999] not found.');

        $useCase->execute($key, new EntityRecord($this->definition));
    }

    public function test_execute_when_validation_fails_propagates_validation_exception(): void
    {
        $key = new EntityKey(1, 'int');

        $this->auth->method('can')->willReturn(true);
        $this->validator
            ->method('validate')
            ->willThrowException(new ValidationException(['name' => ['Required.']]));

        $useCase = $this->makeUseCase();

        $this->expectException(ValidationException::class);

        $useCase->execute($key, new EntityRecord($this->definition));
    }

    public function test_execute_when_unauthorized_does_not_call_validator(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->validator->expects(self::never())->method('validate');

        $useCase = $this->makeUseCase();

        try {
            $useCase->execute(new EntityKey(1), new EntityRecord($this->definition));
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_when_unauthorized_does_not_call_mutator(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->mutator->expects(self::never())->method('update');

        $useCase = $this->makeUseCase();

        try {
            $useCase->execute(new EntityKey(1), new EntityRecord($this->definition));
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
            $useCase->execute(new EntityKey(1), new EntityRecord($this->definition));
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_when_updated_record_has_null_id_throws_logic_exception(): void
    {
        // Mirrors the create-side invariant: mutator->update() must return a
        // hydrated record with a non-null id. Anything else means the write
        // silently failed (e.g. blocked by a model observer that returned false).
        $key = new EntityKey(1, 'int');
        $inputRecord = new EntityRecord($this->definition, ['name' => 'Ghost']);
        $updatedRecord = new EntityRecord($this->definition, ['name' => 'Ghost']); // no id

        $this->auth->method('can')->willReturn(true);
        $this->validator->method('validate');
        $this->mutator->method('update')->willReturn($updatedRecord);
        $this->dispatcher->expects(self::never())->method('dispatch');

        $useCase = $this->makeUseCase();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/persistence invariant/i');

        $useCase->execute($key, $inputRecord);
    }

    public function test_execute_dispatches_entity_updated_event_with_key_and_updated_attributes(): void
    {
        $key = new EntityKey(5, 'int');
        $inputRecord = new EntityRecord($this->definition, ['name' => 'Dave']);
        $updatedRecord = new EntityRecord($this->definition, ['id' => 5, 'name' => 'Dave']);

        $this->auth->method('can')->willReturn(true);
        $this->validator->method('validate');
        $this->mutator->method('update')->willReturn($updatedRecord);

        $dispatchedEvent = null;
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (EntityUpdated $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            });

        $useCase = $this->makeUseCase();
        $useCase->execute($key, $inputRecord);

        self::assertNotNull($dispatchedEvent);
        self::assertSame(5, $dispatchedEvent->getEntityKey()->value);
        self::assertSame(['id' => 5, 'name' => 'Dave'], $dispatchedEvent->getPayload());
    }

    // -------------------------------------------------------------------------
    // A4 (use-case level) — LocaleProvider drives locale-aware validation in update
    // -------------------------------------------------------------------------

    /**
     * Pre-IMPL: makeUseCase() throws ArgumentCountError (6th arg, 5-arg ctor) → ERROR → RED.
     * Post-IMPL: update use case consumes LocaleProvider; validator receives locale-aware keys.
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

        $updatedRecord = new EntityRecord($definition, ['id' => 7]);
        $this->mutator->method('update')->willReturn($updatedRecord);
        $this->dispatcher->method('dispatch');

        // Inline ctor — also fails pre-IMPL (ArgumentCountError on 5-arg ctor).
        $useCase = new UpdateRecordUseCase(
            $this->mutator,
            $this->auth,
            $definition,
            $this->validator,
            $this->dispatcher,
            $this->localeProvider, // 6th arg — locale provider supplying ['en', 'uk']
        );

        $key = new EntityKey(7, 'int');
        $useCase->execute($key, new EntityRecord($definition, ['title' => ['en' => 'Hello', 'uk' => 'Привіт']]));

        // Post-IMPL: update use case builds locale-aware RuleBuilder from LocaleProvider.
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
    }

    /**
     * @return array{0: UpdateRecordUseCase, 1: EntityDefinitionContract, 2: MockObject&EntityMutatorInterface}
     */
    private function makeNonUpdatableUseCase(bool $enforceCapability, bool $authorized): array
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('capabilities')->willReturn(
            EntityCapabilities::all()->without(Capability::Update),
        );
        $definition->method('name')->willReturn('publications');
        $definition->method('keyField')->willReturn('id');
        $definition->method('keyType')->willReturn('int');
        $definition->method('fields')->willReturn([]);

        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->method('can')->willReturn($authorized);
        $mutator = $this->createMock(EntityMutatorInterface::class);

        $useCase = new UpdateRecordUseCase(
            $mutator,
            $auth,
            $definition,
            $this->createMock(ValidationProviderContract::class),
            $this->createMock(EventDispatcherContract::class),
            $this->createMock(LocaleProviderContract::class),
            enforceCapability: $enforceCapability,
        );

        return [$useCase, $definition, $mutator];
    }

    public function test_execute_on_non_updatable_definition_is_denied_by_default(): void
    {
        [$useCase, $definition, $mutator] = $this->makeNonUpdatableUseCase(true, true);
        $mutator->expects(self::never())->method('update');

        $this->expectException(CapabilityDeniedException::class);
        $useCase->execute(new EntityKey(1, 'int'), new EntityRecord($definition, ['title' => 'x']));
    }

    public function test_execute_without_capability_enforcement_updates_embedded_child(): void
    {
        [$useCase, $definition, $mutator] = $this->makeNonUpdatableUseCase(false, true);
        $updated = new EntityRecord($definition, ['id' => 1, 'title' => 'x']);
        $mutator->expects(self::once())->method('update')->willReturn($updated);

        self::assertSame(
            $updated,
            $useCase->execute(new EntityKey(1, 'int'), new EntityRecord($definition, ['title' => 'x'])),
        );
    }

    public function test_execute_without_capability_enforcement_still_authorizes_first(): void
    {
        [$useCase, $definition, $mutator] = $this->makeNonUpdatableUseCase(false, false);
        $mutator->expects(self::never())->method('update');

        $this->expectException(UnauthorizedException::class);
        $useCase->execute(new EntityKey(1, 'int'), new EntityRecord($definition, ['title' => 'x']));
    }
}
