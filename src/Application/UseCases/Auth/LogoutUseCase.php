<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Auth;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthenticationProviderContract;

final readonly class LogoutUseCase
{
    public function __construct(
        private AuthenticationProviderContract $authenticationProvider,
    ) {}

    public function execute(): void
    {
        $this->authenticationProvider->logout();
    }
}
