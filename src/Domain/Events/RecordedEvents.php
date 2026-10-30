<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Events;

use BlackParadise\CoreAdmin\Domain\Contracts\Events\DomainEventContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;

/**
 * Scope-owned event buffer. While at least one write scope is open, events are
 * recorded; the outermost scope's commit releases them to the inner dispatcher,
 * rollback discards them. Outside a scope events are dispatched immediately.
 */
final class RecordedEvents implements EventDispatcherContract
{
    /** @var list<DomainEventContract> */
    private array $recorded = [];

    /** @var list<int> Buffer length at the moment each open scope began. */
    private array $marks = [];

    public function __construct(private readonly EventDispatcherContract $inner) {}

    public function begin(): void
    {
        $this->marks[] = count($this->recorded);
    }

    /** Commit of a scope: the outermost one releases the recorded events. */
    public function commit(): void
    {
        if ($this->marks === []) {
            return;
        }
        array_pop($this->marks);
        if ($this->marks !== []) {
            return;
        }

        $events = $this->recorded;
        $this->recorded = [];
        foreach ($events as $event) {
            $this->inner->dispatch($event);
        }
    }

    /** Rollback of a scope: drops only the events recorded since its begin(). */
    public function rollback(): void
    {
        $mark = array_pop($this->marks);
        if ($mark === null) {
            return;
        }
        $this->recorded = array_slice($this->recorded, 0, $mark);
    }

    public function isRecording(): bool
    {
        return $this->marks !== [];
    }

    public function dispatch(DomainEventContract $event): void
    {
        if ($this->marks !== []) {
            $this->recorded[] = $event;
            return;
        }

        $this->inner->dispatch($event);
    }
}
