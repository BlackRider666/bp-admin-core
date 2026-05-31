<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\ListRecordsUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Query\Criteria;
use BlackParadise\CoreAdmin\Domain\Query\PaginatedResult;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ListRecordsUseCaseTest extends TestCase
{
    private EntityRepositoryInterface $repository;
    private AuthorizationProviderContract $auth;
    private EntityDefinitionContract $definition;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepositoryInterface::class);
        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('name')->willReturn('users');
    }

    public function test_execute_when_authorized_returns_paginated_result_from_repository(): void
    {
        $criteria = new Criteria();
        $expectedResult = new PaginatedResult(items: [], total: 0, page: 1, perPage: 25);

        $this->auth->method('can')->with('list', $this->definition)->willReturn(true);
        $this->repository->method('list')->with($this->definition, $criteria)->willReturn($expectedResult);

        $useCase = new ListRecordsUseCase($this->repository, $this->auth, $this->definition);

        $result = $useCase->execute($criteria);

        self::assertSame($expectedResult, $result);
    }

    public function test_execute_when_unauthorized_throws_unauthorized_exception(): void
    {
        $this->auth->method('can')->with('list', $this->definition)->willReturn(false);

        $useCase = new ListRecordsUseCase($this->repository, $this->auth, $this->definition);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You can't list users");

        $useCase->execute(new Criteria());
    }

    public function test_execute_when_unauthorized_does_not_call_repository(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->repository->expects(self::never())->method('list');

        $useCase = new ListRecordsUseCase($this->repository, $this->auth, $this->definition);

        try {
            $useCase->execute(new Criteria());
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_passes_criteria_to_repository(): void
    {
        $criteria = new Criteria(page: 2, perPage: 10, search: 'alice');
        $result = new PaginatedResult(items: [], total: 0, page: 2, perPage: 10);

        $this->auth->method('can')->willReturn(true);
        $this->repository
            ->expects(self::once())
            ->method('list')
            ->with($this->definition, $criteria)
            ->willReturn($result);

        $useCase = new ListRecordsUseCase($this->repository, $this->auth, $this->definition);
        $useCase->execute($criteria);
    }
}
