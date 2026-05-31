<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts;

interface TransactionContract
{
    /**
     * Execute the given callable inside a transaction.
     *
     * Implementations must commit on successful return and roll back if
     * the callable throws. The callable's return value is passed through
     * unchanged to the caller.
     *
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function executeInTransaction(callable $work): mixed;
}
