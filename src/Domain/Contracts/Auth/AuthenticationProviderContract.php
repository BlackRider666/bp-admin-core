<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Auth;

interface AuthenticationProviderContract
{
    public function attempt(array $credentials): bool;
    public function logout(): void;
    public function user(): ?object;
}
