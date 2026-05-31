<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\TransactionContract;

/**
 * In-memory test double for {@see TransactionContract}.
 *
 * Просто викликає $work() без обгортки. Використовується в Domain/Application
 * unit-тестах, де реальна БД-транзакція не потрібна.
 *
 * Якщо $work() throws — exception проходить наскрізь (як у production через
 * DB::transaction rollback + rethrow).
 */
final class InMemoryTransaction implements TransactionContract
{
    public function executeInTransaction(callable $work): mixed
    {
        return $work();
    }
}
