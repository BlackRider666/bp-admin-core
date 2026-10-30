<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\DeleteEntityGraphUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\OwnedDeletesCollectorContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Events\EntityDeleted;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Mutators\EntityMutatorInterface;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\Dyn;
use Tests\Doubles\InMemoryAuthorizationProvider;
use Tests\Doubles\InMemoryEntityRepository;
use Tests\Doubles\InMemoryWriteScope;
use Tests\Doubles\RecordingEventDispatcher;
use Tests\Fixtures\StubEntityDefinition;
use Tests\Fixtures\StubNestedChildDefinition;

/**
 * Pins DeleteEntityGraphUseCase (A24, A25, A27, A54, A55).
 *
 * Pinned API: named ctor args authorizationProvider, writeScope, repository, mutator, ownedCollector
 * (OwnedDeletesCollectorContract), dispatcher. execute(EntityDefinitionContract, EntityKey): bool.
 * Soft-delete awareness (A54 vs A55) lives in the collector: it returns [] for a soft-deletable host
 * (soft-delete only) and the owned children otherwise (force-delete / non-soft-deletable host).
 * The use case deletes host and every collected child through the mutator, emits one EntityDeleted per
 * deleted record, and does it all inside ONE write scope.
 */
final class DeleteEntityGraphUseCaseTest extends TestCase
{
    private EntityDefinitionContract $host;
    private EntityDefinitionContract $child;
    private InMemoryAuthorizationProvider $auth;
    private InMemoryWriteScope $scope;
    private RecordingEventDispatcher $events;
    private InMemoryEntityRepository $repository;

    /** @var list<array{string, int|string}> */
    private array $deleted = [];

    protected function setUp(): void
    {
        $this->host = new StubEntityDefinition();
        $this->child = new StubNestedChildDefinition();
        $this->auth = new InMemoryAuthorizationProvider();
        $this->events = new RecordingEventDispatcher();
        $this->scope = (new InMemoryWriteScope())->attach($this->events);
        $this->repository = new InMemoryEntityRepository();
        $this->repository->seed($this->host, new EntityRecord($this->host, ['id' => 1]));
        $this->deleted = [];
    }

    /** @param list<array{def: EntityDefinitionContract, key: EntityKey}> $owned */
    private function collector(array $owned): OwnedDeletesCollectorContract
    {
        $collector = $this->createMock(OwnedDeletesCollectorContract::class);
        $collector->method('collect')->willReturn($owned);

        return $collector;
    }

    private function mutator(): EntityMutatorInterface
    {
        $mutator = $this->createMock(EntityMutatorInterface::class);
        $mutator->method('delete')->willReturnCallback(function (EntityKey $key, EntityDefinitionContract $def): bool {
            $this->deleted[] = [$def->name(), $key->value];
            return true;
        });

        return $mutator;
    }

    private function makeUseCase(
        ?OwnedDeletesCollectorContract $collector = null,
        ?EntityMutatorInterface $mutator = null,
        ?EntityRepositoryInterface $repository = null,
    ): object {
        return Dyn::make(DeleteEntityGraphUseCase::class, [
            'authorizationProvider' => $this->auth,
            'writeScope' => $this->scope,
            'repository' => $repository ?? $this->repository,
            'mutator' => $mutator ?? $this->mutator(),
            'ownedCollector' => $collector ?? $this->collector([]),
            'dispatcher' => $this->events,
        ]);
    }

    public function test_execute_when_unauthorized_throws_before_any_read_scope_or_write(): void
    {
        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository->expects(self::never())->method('find');
        $collector = $this->createMock(OwnedDeletesCollectorContract::class);
        $collector->expects(self::never())->method('collect');
        $mutator = $this->createMock(EntityMutatorInterface::class);
        $mutator->expects(self::never())->method('delete');
        $useCase = $this->makeUseCase($collector, $mutator, $repository);

        try {
            Dyn::call($useCase, 'execute', $this->host, new EntityKey(1));
            self::fail('Expected UnauthorizedException.');
        } catch (UnauthorizedException) {
            self::assertSame(0, $this->scope->runCalls);
        }
    }

    public function test_execute_when_key_missing_throws_entity_not_found_and_deletes_nothing(): void
    {
        $this->auth->allowAll();
        $useCase = $this->makeUseCase();

        try {
            Dyn::call($useCase, 'execute', $this->host, new EntityKey(404));
            self::fail('Expected EntityNotFoundException.');
        } catch (EntityNotFoundException) {
            self::assertSame([], $this->deleted);
            self::assertSame([], $this->events->dispatched);
        }
    }

    public function test_execute_without_owned_children_deletes_only_host_and_returns_true(): void
    {
        $this->auth->allowAll();
        $useCase = $this->makeUseCase();

        $result = Dyn::call($useCase, 'execute', $this->host, new EntityKey(1));

        self::assertTrue($result);
        self::assertSame([['stub', 1]], $this->deleted);
        self::assertSame(1, $this->scope->commits);
    }

    public function test_execute_for_soft_deletable_host_leaves_children_untouched_and_fires_only_host_event(): void
    {
        // A54: the collector yields no owned children for a soft-delete.
        $this->auth->allowAll();
        $useCase = $this->makeUseCase($this->collector([]));

        Dyn::call($useCase, 'execute', $this->host, new EntityKey(1));

        self::assertSame([['stub', 1]], $this->deleted);
        self::assertCount(1, $this->events->dispatched);
    }

    public function test_execute_with_owned_children_deletes_host_and_all_children_in_one_scope(): void
    {
        // A25 / A55: owned hasMany/morphMany children are deleted through BPAdmin.
        $this->auth->allowAll();
        $owned = [
            ['def' => $this->child, 'key' => new EntityKey(7)],
            ['def' => $this->child, 'key' => new EntityKey(8)],
        ];
        $useCase = $this->makeUseCase($this->collector($owned));

        Dyn::call($useCase, 'execute', $this->host, new EntityKey(1));

        $deleted = $this->deleted;
        sort($deleted);
        self::assertSame([['children', 7], ['children', 8], ['stub', 1]], $deleted);
        self::assertSame(1, $this->scope->runCalls);
    }

    public function test_execute_with_owned_children_dispatches_entity_deleted_per_record_after_commit(): void
    {
        $this->auth->allowAll();
        $owned = [
            ['def' => $this->child, 'key' => new EntityKey(7)],
            ['def' => $this->child, 'key' => new EntityKey(8)],
        ];
        $useCase = $this->makeUseCase($this->collector($owned));

        Dyn::call($useCase, 'execute', $this->host, new EntityKey(1));

        self::assertCount(3, $this->events->dispatched);
        foreach ($this->events->dispatched as $i => $event) {
            self::assertInstanceOf(EntityDeleted::class, $event);
            self::assertGreaterThanOrEqual(1, $this->events->commitsAtDelivery[$i]);
        }
    }

    public function test_execute_when_child_delete_unauthorized_aborts_whole_delete_without_events(): void
    {
        $this->auth->allow('stub', 'delete'); // host allowed, 'children' denied
        $owned = [['def' => $this->child, 'key' => new EntityKey(7)]];
        $useCase = $this->makeUseCase($this->collector($owned));

        try {
            Dyn::call($useCase, 'execute', $this->host, new EntityKey(1));
            self::fail('Expected UnauthorizedException.');
        } catch (UnauthorizedException) {
            self::assertSame(1, $this->scope->rollbacks);
            self::assertSame(0, $this->scope->commits);
            self::assertSame([], $this->events->dispatched);
        }
    }

    public function test_execute_when_host_delete_fails_throws_entity_not_found_and_rolls_back(): void
    {
        $this->auth->allowAll();
        $mutator = $this->createMock(EntityMutatorInterface::class);
        $mutator->method('delete')->willReturn(false);
        $useCase = $this->makeUseCase(null, $mutator);

        try {
            Dyn::call($useCase, 'execute', $this->host, new EntityKey(1));
            self::fail('Expected EntityNotFoundException.');
        } catch (EntityNotFoundException) {
            self::assertSame(1, $this->scope->rollbacks);
            self::assertSame([], $this->events->dispatched);
        }
    }
}
