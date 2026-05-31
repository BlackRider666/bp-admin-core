<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Auth;

use BlackParadise\CoreAdmin\Application\UseCases\Auth\LogoutUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthenticationProviderContract;
use PHPUnit\Framework\TestCase;

final class LogoutUseCaseTest extends TestCase
{
    public function test_execute_calls_logout_on_authentication_provider(): void
    {
        $auth = $this->createMock(AuthenticationProviderContract::class);
        $auth->expects(self::once())->method('logout');

        $useCase = new LogoutUseCase($auth);
        $useCase->execute();
    }

    public function test_execute_returns_void(): void
    {
        $auth = $this->createMock(AuthenticationProviderContract::class);
        $auth->method('logout');

        $useCase = new LogoutUseCase($auth);
        $result = $useCase->execute();

        self::assertNull($result);
    }
}
