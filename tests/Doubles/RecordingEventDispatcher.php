<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\Events\DomainEventContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;

/**
 * Event dispatcher that emulates scope-owned recorded events.
 *
 * While the bound {@see InMemoryWriteScope} is active, dispatch() buffers events;
 * they are delivered (to $dispatched) only when the scope commits, and dropped on
 * rollback. Outside a scope events are delivered immediately.
 * $commitsAtDelivery[i] = scope commit count when $dispatched[i] was delivered
 * (>= 1 means "delivered after commit").
 */
final class RecordingEventDispatcher implements EventDispatcherContract
{
    /** @var list<DomainEventContract> */
    public array $dispatched = [];

    /** @var list<int> */
    public array $commitsAtDelivery = [];

    /** @var list<DomainEventContract> */
    private array $pending = [];

    private ?InMemoryWriteScope $scope = null;

    public function bindScope(InMemoryWriteScope $scope): void
    {
        $this->scope = $scope;
    }

    public function dispatch(DomainEventContract $event): void
    {
        if ($this->scope instanceof InMemoryWriteScope && $this->scope->active) {
            $this->pending[] = $event;
            return;
        }

        $this->deliver($event);
    }

    public function releasePending(): void
    {
        $pending = $this->pending;
        $this->pending = [];

        foreach ($pending as $event) {
            $this->deliver($event);
        }
    }

    public function discardPending(): void
    {
        $this->pending = [];
    }

    private function deliver(DomainEventContract $event): void
    {
        $this->dispatched[] = $event;
        $this->commitsAtDelivery[] = $this->scope->commits ?? 0;
    }
}
