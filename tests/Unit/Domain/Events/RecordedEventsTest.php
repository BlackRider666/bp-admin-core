<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Events;

use BlackParadise\CoreAdmin\Domain\Events\EntityCreated;
use BlackParadise\CoreAdmin\Domain\Events\RecordedEvents;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\InMemoryEventDispatcher;

final class RecordedEventsTest extends TestCase
{
    private InMemoryEventDispatcher $inner;
    private RecordedEvents $recorded;

    protected function setUp(): void
    {
        $this->inner = new InMemoryEventDispatcher();
        $this->recorded = new RecordedEvents($this->inner);
    }

    private function event(int $id = 1): EntityCreated
    {
        return new EntityCreated(new EntityKey($id));
    }

    public function test_dispatch_outside_scope_forwards_immediately(): void
    {
        $this->recorded->dispatch($this->event());

        self::assertCount(1, $this->inner->dispatched);
        self::assertFalse($this->recorded->isRecording());
    }

    public function test_dispatch_inside_scope_is_buffered_until_commit(): void
    {
        $this->recorded->begin();
        $this->recorded->dispatch($this->event());

        self::assertTrue($this->recorded->isRecording());
        self::assertSame([], $this->inner->dispatched);

        $this->recorded->commit();

        self::assertCount(1, $this->inner->dispatched);
        self::assertFalse($this->recorded->isRecording());
    }

    public function test_commit_releases_events_in_recorded_order(): void
    {
        $first = $this->event(1);
        $second = $this->event(2);
        $this->recorded->begin();
        $this->recorded->dispatch($first);
        $this->recorded->dispatch($second);

        $this->recorded->commit();

        self::assertSame([$first, $second], $this->inner->dispatched);
    }

    public function test_rollback_discards_recorded_events(): void
    {
        $this->recorded->begin();
        $this->recorded->dispatch($this->event());

        $this->recorded->rollback();

        self::assertSame([], $this->inner->dispatched);
        self::assertFalse($this->recorded->isRecording());
    }

    public function test_rolled_back_events_are_not_released_by_a_later_commit(): void
    {
        $this->recorded->begin();
        $this->recorded->dispatch($this->event());
        $this->recorded->rollback();

        $this->recorded->begin();
        $this->recorded->commit();

        self::assertSame([], $this->inner->dispatched);
    }

    public function test_nested_inner_commit_does_not_release_events(): void
    {
        $this->recorded->begin();
        $this->recorded->begin();
        $this->recorded->dispatch($this->event());

        $this->recorded->commit();

        self::assertSame([], $this->inner->dispatched);
        self::assertTrue($this->recorded->isRecording());
    }

    public function test_only_outermost_commit_releases_nested_events(): void
    {
        $this->recorded->begin();
        $this->recorded->begin();
        $this->recorded->dispatch($this->event());
        $this->recorded->commit();

        $this->recorded->commit();

        self::assertCount(1, $this->inner->dispatched);
    }

    public function test_nested_rollback_drops_only_the_inner_scopes_events(): void
    {
        $this->recorded->begin();
        $this->recorded->begin();
        $this->recorded->dispatch($this->event());

        $this->recorded->rollback();

        self::assertSame([], $this->inner->dispatched);
        self::assertTrue($this->recorded->isRecording());

        $this->recorded->commit();

        self::assertSame([], $this->inner->dispatched);
    }

    public function test_outer_events_survive_inner_rollback_and_release_on_outer_commit(): void
    {
        $outer = $this->event(1);
        $inner = $this->event(2);
        $this->recorded->begin();
        $this->recorded->dispatch($outer);
        $this->recorded->begin();
        $this->recorded->dispatch($inner);
        $this->recorded->rollback();

        $this->recorded->commit();

        self::assertSame([$outer], $this->inner->dispatched);
    }

    public function test_partial_success_sequence_releases_only_committed_scopes_events_in_order(): void
    {
        $first = $this->event(1);
        $failed = $this->event(2);
        $third = $this->event(3);
        $this->recorded->begin();
        $this->recorded->begin();
        $this->recorded->dispatch($first);
        $this->recorded->commit();
        $this->recorded->begin();
        $this->recorded->dispatch($failed);
        $this->recorded->rollback();
        $this->recorded->begin();
        $this->recorded->dispatch($third);
        $this->recorded->commit();

        self::assertSame([], $this->inner->dispatched);

        $this->recorded->commit();

        self::assertSame([$first, $third], $this->inner->dispatched);
    }

    public function test_outermost_rollback_drops_all_recorded_events(): void
    {
        $this->recorded->begin();
        $this->recorded->dispatch($this->event(1));
        $this->recorded->begin();
        $this->recorded->dispatch($this->event(2));
        $this->recorded->commit();

        $this->recorded->rollback();

        self::assertSame([], $this->inner->dispatched);
        self::assertFalse($this->recorded->isRecording());
    }

    public function test_commit_and_rollback_outside_scope_are_noops(): void
    {
        $this->recorded->commit();
        $this->recorded->rollback();
        $this->recorded->dispatch($this->event());

        self::assertCount(1, $this->inner->dispatched);
        self::assertFalse($this->recorded->isRecording());
    }
}
