<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\Events\DomainEventContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\EventDispatcherContract;

/**
 * Recording event dispatcher for tests.
 *
 *     $events = new InMemoryEventDispatcher();
 *     $useCase->execute(...);
 *     $this->assertCount(1, $events->dispatched);
 */
final class InMemoryEventDispatcher implements EventDispatcherContract
{
    /** @var list<DomainEventContract> */
    public array $dispatched = [];

    public function dispatch(DomainEventContract $event): void
    {
        $this->dispatched[] = $event;
    }

    /**
     * @return list<DomainEventContract>
     */
    public function dispatched(): array
    {
        return $this->dispatched;
    }

    public function reset(): void
    {
        $this->dispatched = [];
    }
}
