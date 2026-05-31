<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Audit;

interface AuditLoggerContract
{
    public function log(string $action, string $entity, mixed $key, array $payload = []): void;
}
