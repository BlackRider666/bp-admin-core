<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Exceptions;

use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Exceptions\ActionNotFoundException;
use BlackParadise\CoreAdmin\Domain\Exceptions\CapabilityDeniedException;
use BlackParadise\CoreAdmin\Domain\Exceptions\InvalidActionScopeException;
use BlackParadise\CoreAdmin\Domain\Exceptions\InvalidDefinitionException;
use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use DomainException;
use PHPUnit\Framework\TestCase;

/** A29 / A36 — new domain exceptions. */
final class NewExceptionsTest extends TestCase
{
    public function test_capability_denied_message_names_entity_and_capability(): void
    {
        $e = new CapabilityDeniedException('users', Capability::Delete);

        self::assertInstanceOf(DomainException::class, $e);
        self::assertStringContainsString('users', $e->getMessage());
        self::assertStringContainsString('delete', $e->getMessage());
    }

    public function test_action_not_found_message_names_entity_and_action(): void
    {
        $e = new ActionNotFoundException('users', 'archive');

        self::assertInstanceOf(DomainException::class, $e);
        self::assertStringContainsString('users', $e->getMessage());
        self::assertStringContainsString('archive', $e->getMessage());
    }

    public function test_invalid_action_scope_message_names_action_and_scope(): void
    {
        $e = new InvalidActionScopeException('archive', ActionScope::Bulk);

        self::assertInstanceOf(DomainException::class, $e);
        self::assertStringContainsString('archive', $e->getMessage());
        self::assertStringContainsString('bulk', $e->getMessage());
    }

    public function test_invalid_definition_carries_given_message(): void
    {
        $e = new InvalidDefinitionException('Field "title" is not searchable.');

        self::assertSame('Field "title" is not searchable.', $e->getMessage());
        self::assertInstanceOf(DomainException::class, $e);
    }

    public function test_invalid_definition_is_thrown_with_class_context_message(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('App\\Foo: bad');

        throw new InvalidDefinitionException('App\\Foo: bad');
    }
}
