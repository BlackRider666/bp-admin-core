<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\DeleteRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;
use BlackParadise\CoreAdmin\Domain\Events\EntityDeleted;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * DeleteRecordUseCase drops the repository->exists() pre-check: the mutator's
 * delete() result is the single source of truth (false => EntityNotFoundException).
 * Constructor signature is pinned unchanged (5 args, same order).
 */
final class DeleteRecordUseCaseNoExistsTest extends TestCase
{
    /** @var EntityRepositoryInterface&MockObject */
    private EntityRepositoryInterface $repository;
    /** @var EntityMutatorInterface&MockObject */
    private EntityMutatorInterface $mutator;
    private AuthorizationProviderContract $auth;
    private EntityDefinitionContract $definition;
    /** @var EventDispatcherContract&MockObject */
    private EventDispatcherContract $dispatcher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepositoryInterface::class);
        $this->mutator = $this->createMock(EntityMutatorInterface::class);
        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('capabilities')->willReturn(EntityCapabilities::all());
        $this->dispatcher = $this->createMock(EventDispatcherContract::class);
        $this->definition->method('name')->willReturn('users');
        $this->auth->method('can')->willReturn(true);
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

    public function test_execute_never_calls_repository_exists(): void
    {
        $this->repository->expects(self::never())->method('exists');
        $this->mutator->method('delete')->willReturn(true);

        $this->makeUseCase()->execute(new EntityKey(1));
    }

    public function test_execute_when_mutator_deletes_returns_true_and_dispatches_entity_deleted(): void
    {
        $this->mutator->method('delete')->willReturn(true);
        $this->dispatcher->expects(self::once())->method('dispatch')->with(self::isInstanceOf(EntityDeleted::class));

        $result = $this->makeUseCase()->execute(new EntityKey(1));

        self::assertTrue($result);
    }

    public function test_execute_when_mutator_returns_false_throws_entity_not_found(): void
    {
        $this->repository->method('exists')->willReturn(true); // legacy pre-check would pass; mutator decides
        $this->mutator->method('delete')->willReturn(false);
        $this->dispatcher->expects(self::never())->method('dispatch');

        $this->expectException(EntityNotFoundException::class);

        $this->makeUseCase()->execute(new EntityKey(1));
    }
}
