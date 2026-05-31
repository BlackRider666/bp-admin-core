<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Events;

use BlackParadise\CoreAdmin\Domain\Events\EntityCreated;
use BlackParadise\CoreAdmin\Domain\Events\EntityDeleted;
use BlackParadise\CoreAdmin\Domain\Events\EntityUpdated;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;

final class DomainEventsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // EntityCreated
    // -------------------------------------------------------------------------

    public function test_entity_created_get_entity_key_returns_injected_key(): void
    {
        $key = new EntityKey(1, 'int');
        $event = new EntityCreated($key);

        self::assertSame($key, $event->getEntityKey());
    }

    public function test_entity_created_get_payload_returns_empty_array_by_default(): void
    {
        $event = new EntityCreated(new EntityKey(1));

        self::assertSame([], $event->getPayload());
    }

    public function test_entity_created_get_payload_returns_injected_payload(): void
    {
        $key = new EntityKey(5, 'int');
        $payload = ['name' => 'Alice', 'email' => 'alice@example.com'];
        $event = new EntityCreated($key, $payload);

        self::assertSame($payload, $event->getPayload());
    }

    // -------------------------------------------------------------------------
    // EntityUpdated
    // -------------------------------------------------------------------------

    public function test_entity_updated_get_entity_key_returns_injected_key(): void
    {
        $key = new EntityKey(10, 'int');
        $event = new EntityUpdated($key);

        self::assertSame($key, $event->getEntityKey());
    }

    public function test_entity_updated_get_payload_returns_empty_array_by_default(): void
    {
        $event = new EntityUpdated(new EntityKey(2));

        self::assertSame([], $event->getPayload());
    }

    public function test_entity_updated_get_payload_returns_injected_payload(): void
    {
        $key = new EntityKey(7, 'int');
        $payload = ['name' => 'Bob', 'status' => 'active'];
        $event = new EntityUpdated($key, $payload);

        self::assertSame($payload, $event->getPayload());
    }

    // -------------------------------------------------------------------------
    // EntityDeleted
    // -------------------------------------------------------------------------

    public function test_entity_deleted_get_entity_key_returns_injected_key(): void
    {
        $key = new EntityKey(99, 'int');
        $event = new EntityDeleted($key);

        self::assertSame($key, $event->getEntityKey());
    }

    public function test_entity_deleted_get_payload_returns_empty_array_by_default(): void
    {
        $event = new EntityDeleted(new EntityKey(3));

        self::assertSame([], $event->getPayload());
    }

    public function test_entity_deleted_get_payload_returns_injected_payload(): void
    {
        $key = new EntityKey(50, 'int');
        $payload = ['deleted_by' => 'admin'];
        $event = new EntityDeleted($key, $payload);

        self::assertSame($payload, $event->getPayload());
    }

    // -------------------------------------------------------------------------
    // Key value types
    // -------------------------------------------------------------------------

    public function test_entity_created_accepts_string_key(): void
    {
        $key = new EntityKey('uuid-abc-123', 'string');
        $event = new EntityCreated($key);

        self::assertSame('uuid-abc-123', $event->getEntityKey()->value);
        self::assertSame('string', $event->getEntityKey()->type);
    }
}
