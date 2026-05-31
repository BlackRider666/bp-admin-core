<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Exceptions;

use BlackParadise\CoreAdmin\Application\Exceptions\EntityNotFoundException;
use DomainException;
use PHPUnit\Framework\TestCase;

final class EntityNotFoundExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $exception = new EntityNotFoundException('users', 42);

        self::assertInstanceOf(DomainException::class, $exception);
    }

    public function test_message_contains_entity_and_key(): void
    {
        $exception = new EntityNotFoundException('users', 42);

        self::assertSame('Entity [users] with key [42] not found.', $exception->getMessage());
    }

    public function test_message_with_string_key(): void
    {
        $exception = new EntityNotFoundException('posts', 'abc-123');

        self::assertSame('Entity [posts] with key [abc-123] not found.', $exception->getMessage());
    }

    public function test_message_with_different_entity_name(): void
    {
        $exception = new EntityNotFoundException('order_items', 99);

        self::assertSame('Entity [order_items] with key [99] not found.', $exception->getMessage());
    }
}
