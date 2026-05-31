<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\UpdateRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Events\EntityUpdated;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use LogicException;
use PHPUnit\Framework\TestCase;

final class UpdateRecordUseCaseTest extends TestCase
{
    private EntityRepositoryInterface $repository;
    private EntityMutatorInterface $mutator;
    private AuthorizationProviderContract $auth;
    private EntityDefinitionContract $definition;
    private ValidationProviderContract $validator;
    private EventDispatcherContract $dispatcher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepositoryInterface::class);
        $this->mutator = $this->createMock(EntityMutatorInterface::class);
        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->validator = $this->createMock(ValidationProviderContract::class);
        $this->dispatcher = $this->createMock(EventDispatcherContract::class);

        $this->definition->method('name')->willReturn('users');
        $this->definition->method('keyField')->willReturn('id');
        $this->definition->method('keyType')->willReturn('int');
        $this->definition->method('fields')->willReturn([]);
    }

    private function makeUseCase(): UpdateRecordUseCase
    {
        return new UpdateRecordUseCase(
            $this->repository,
            $this->mutator,
            $this->auth,
            $this->definition,
            $this->validator,
            $this->dispatcher,
        );
    }

    public function test_execute_when_authorized_and_exists_updates_record_and_dispatches_event(): void
    {
        $key = new EntityKey(1, 'int');
        $inputRecord = new EntityRecord($this->definition, ['name' => 'Alice Updated']);
        $updatedRecord = new EntityRecord($this->definition, ['id' => 1, 'name' => 'Alice Updated']);

        $this->auth->method('can')->with('update', $this->definition)->willReturn(true);
        $this->repository->method('exists')->with($this->definition, $key)->willReturn(true);
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
        $this->repository->method('exists')->with($this->definition, $key)->willReturn(false);

        $useCase = $this->makeUseCase();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Entity [users] with key [999] not found.');

        $useCase->execute($key, new EntityRecord($this->definition));
    }

    public function test_execute_when_validation_fails_propagates_validation_exception(): void
    {
        $key = new EntityKey(1, 'int');

        $this->auth->method('can')->willReturn(true);
        $this->repository->method('exists')->willReturn(true);
        $this->validator
            ->method('validate')
            ->willThrowException(new ValidationException(['name' => ['Required.']]));

        $useCase = $this->makeUseCase();

        $this->expectException(ValidationException::class);

        $useCase->execute($key, new EntityRecord($this->definition));
    }

    public function test_execute_when_unauthorized_does_not_check_existence(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->repository->expects(self::never())->method('exists');

        $useCase = $this->makeUseCase();

        try {
            $useCase->execute(new EntityKey(1), new EntityRecord($this->definition));
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
        $this->repository->method('exists')->willReturn(true);
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
        $this->repository->method('exists')->willReturn(true);
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
}
