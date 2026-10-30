<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\Persistence\WriteScopeContract;
use Throwable;

/**
 * Test double for {@see WriteScopeContract}.
 *
 * Mirrors the production contract: run() executes the work atomically; on success it
 * counts a commit and then releases buffered events of the attached dispatcher;
 * on any Throwable it counts a rollback, discards the buffered events and rethrows.
 */
final class InMemoryWriteScope implements WriteScopeContract
{
    public int $runCalls = 0;
    public int $commits = 0;
    public int $rollbacks = 0;
    public bool $active = false;

    private ?RecordingEventDispatcher $dispatcher = null;

    public function attach(RecordingEventDispatcher $dispatcher): self
    {
        $this->dispatcher = $dispatcher;
        $dispatcher->bindScope($this);
        return $this;
    }

    public function run(callable $work): mixed
    {
        ++$this->runCalls;
        $this->active = true;

        try {
            $result = $work();
        } catch (Throwable $e) {
            $this->active = false;
            ++$this->rollbacks;
            $this->dispatcher?->discardPending();
            throw $e;
        }

        $this->active = false;
        ++$this->commits;
        $this->dispatcher?->releasePending();

        return $result;
    }
}
