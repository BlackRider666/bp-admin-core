<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\DeleteRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Events\EntityDeleted;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;

final class DeleteRecordUseCaseTest extends TestCase
{
    private EntityRepositoryInterface $repository;
    private EntityMutatorInterface $mutator;
    private AuthorizationProviderContract $auth;
    private EntityDefinitionContract $definition;
    private EventDispatcherContract $dispatcher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepositoryInterface::class);
        $this->mutator = $this->createMock(EntityMutatorInterface::class);
        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->dispatcher = $this->createMock(EventDispatcherContract::class);

        $this->definition->method('name')->willReturn('users');
    }

    private function makeUseCase(): DeleteRecordUseCase
    {
        return new DeleteRecordUseCase(
            $this->repository,
            $this->mutator,
            $this->auth,
            $this->definition,
            $this->dispatcher,
        );
    }

    public function test_execute_when_authorized_and_exists_and_delete_succeeds_returns_true(): void
    {
        $key = new EntityKey(1, 'int');

        $this->auth->method('can')->with('delete', $this->definition)->willReturn(true);
        $this->repository->method('exists')->with($this->definition, $key)->willReturn(true);
        $this->mutator->method('delete')->with($key, $this->definition)->willReturn(true);
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EntityDeleted::class));

        $useCase = $this->makeUseCase();
        $result = $useCase->execute($key);

        self::assertTrue($result);
    }

    public function test_execute_when_delete_fails_does_not_dispatch_event_and_returns_false(): void
    {
        $key = new EntityKey(1, 'int');

        $this->auth->method('can')->willReturn(true);
        $this->repository->method('exists')->willReturn(true);
        $this->mutator->method('delete')->willReturn(false);
        $this->dispatcher->expects(self::never())->method('dispatch');

        $useCase = $this->makeUseCase();
        $result = $useCase->execute($key);

        self::assertFalse($result);
    }

    public function test_execute_when_unauthorized_throws_unauthorized_exception(): void
    {
        $this->auth->method('can')->with('delete', $this->definition)->willReturn(false);

        $useCase = $this->makeUseCase();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You can't delete users");

        $useCase->execute(new EntityKey(1));
    }

    public function test_execute_when_record_not_found_throws_entity_not_found_exception(): void
    {
        $key = new EntityKey(404, 'int');

        $this->auth->method('can')->willReturn(true);
        $this->repository->method('exists')->with($this->definition, $key)->willReturn(false);

        $useCase = $this->makeUseCase();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Entity [users] with key [404] not found.');

        $useCase->execute($key);
    }

    public function test_execute_when_unauthorized_does_not_check_existence(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->repository->expects(self::never())->method('exists');

        $useCase = $this->makeUseCase();

        try {
            $useCase->execute(new EntityKey(1));
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_when_delete_succeeds_dispatches_event_with_correct_key(): void
    {
        $key = new EntityKey(77, 'int');

        $this->auth->method('can')->willReturn(true);
        $this->repository->method('exists')->willReturn(true);
        $this->mutator->method('delete')->willReturn(true);

        $dispatchedEvent = null;
        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (EntityDeleted $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            });

        $useCase = $this->makeUseCase();
        $useCase->execute($key);

        self::assertNotNull($dispatchedEvent);
        self::assertSame(77, $dispatchedEvent->getEntityKey()->value);
    }
}
