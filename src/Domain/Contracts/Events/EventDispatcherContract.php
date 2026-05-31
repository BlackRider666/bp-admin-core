<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Events;

interface EventDispatcherContract
{
    /**
     * Dispatch a domain event to all registered listeners.
     */
    public function dispatch(DomainEventContract $event): void;
}
