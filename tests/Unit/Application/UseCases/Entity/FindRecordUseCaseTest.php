<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\FindRecordUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Repositories\EntityRepositoryInterface;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;

final class FindRecordUseCaseTest extends TestCase
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

    public function test_execute_when_authorized_and_record_found_returns_entity_record(): void
    {
        $key = new EntityKey(1, 'int');
        $record = new EntityRecord($this->definition, ['id' => 1]);

        $this->auth->method('can')->with('view', $this->definition)->willReturn(true);
        $this->repository->method('find')->with($this->definition, $key)->willReturn($record);

        $useCase = new FindRecordUseCase($this->repository, $this->auth, $this->definition);

        $result = $useCase->execute($key);

        self::assertSame($record, $result);
    }

    public function test_execute_when_unauthorized_throws_unauthorized_exception(): void
    {
        $this->auth->method('can')->with('view', $this->definition)->willReturn(false);

        $useCase = new FindRecordUseCase($this->repository, $this->auth, $this->definition);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You can't view users");

        $useCase->execute(new EntityKey(1));
    }

    public function test_execute_when_record_not_found_throws_entity_not_found_exception(): void
    {
        $key = new EntityKey(999, 'int');

        $this->auth->method('can')->willReturn(true);
        $this->repository->method('find')->with($this->definition, $key)->willReturn(null);

        $useCase = new FindRecordUseCase($this->repository, $this->auth, $this->definition);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Entity [users] with key [999] not found.');

        $useCase->execute($key);
    }

    public function test_execute_when_unauthorized_does_not_call_repository(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->repository->expects(self::never())->method('find');

        $useCase = new FindRecordUseCase($this->repository, $this->auth, $this->definition);

        try {
            $useCase->execute(new EntityKey(1));
        } catch (UnauthorizedException) {
            // expected
        }
    }
}
