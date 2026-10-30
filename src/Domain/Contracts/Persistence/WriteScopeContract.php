<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Persistence;

interface WriteScopeContract
{
    /** Runs $work inside one atomic write scope; returns its result. */
    public function run(callable $work): mixed;
}
