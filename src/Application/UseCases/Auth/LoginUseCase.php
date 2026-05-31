<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Auth;

use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthenticationProviderContract;

final readonly class LoginUseCase
{
    public function __construct(
        private AuthenticationProviderContract $authenticationProvider,
    ) {}

    public function execute(array $credentials): bool
    {
        return $this->authenticationProvider->attempt($credentials);
    }
}
