<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Auth;

use BlackParadise\CoreAdmin\Application\UseCases\Auth\LoginUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthenticationProviderContract;
use PHPUnit\Framework\TestCase;

final class LoginUseCaseTest extends TestCase
{
    public function test_execute_returns_true_when_credentials_are_valid(): void
    {
        $auth = $this->createMock(AuthenticationProviderContract::class);
        $auth->method('attempt')
            ->with(['email' => 'admin@example.com', 'password' => 'secret'])
            ->willReturn(true);

        $useCase = new LoginUseCase($auth);
        $result = $useCase->execute(['email' => 'admin@example.com', 'password' => 'secret']);

        self::assertTrue($result);
    }

    public function test_execute_returns_false_when_credentials_are_invalid(): void
    {
        $auth = $this->createMock(AuthenticationProviderContract::class);
        $auth->method('attempt')
            ->with(['email' => 'bad@example.com', 'password' => 'wrong'])
            ->willReturn(false);

        $useCase = new LoginUseCase($auth);
        $result = $useCase->execute(['email' => 'bad@example.com', 'password' => 'wrong']);

        self::assertFalse($result);
    }

    public function test_execute_delegates_credentials_to_authentication_provider(): void
    {
        $credentials = ['email' => 'user@example.com', 'password' => 'pass123'];

        $auth = $this->createMock(AuthenticationProviderContract::class);
        $auth->expects(self::once())
            ->method('attempt')
            ->with($credentials)
            ->willReturn(true);

        $useCase = new LoginUseCase($auth);
        $useCase->execute($credentials);
    }
}
