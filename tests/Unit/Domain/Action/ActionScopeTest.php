<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Action;

use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use PHPUnit\Framework\TestCase;

/** A35 — action scopes Row|Bulk|Entity. */
final class ActionScopeTest extends TestCase
{
    public function test_scope_enum_has_row_bulk_entity_cases(): void
    {
        self::assertTrue(enum_exists(ActionScope::class), 'ActionScope enum must exist.');
        self::assertSame(
            ['Row', 'Bulk', 'Entity'],
            array_map(static fn(ActionScope $s): string => $s->name, ActionScope::cases()),
        );
    }

    public function test_scope_values_are_lowercase_strings(): void
    {
        self::assertSame('row', ActionScope::Row->value);
        self::assertSame('bulk', ActionScope::Bulk->value);
        self::assertSame('entity', ActionScope::Entity->value);
    }

    public function test_scope_resolves_from_string_value(): void
    {
        self::assertSame(ActionScope::Bulk, ActionScope::from('bulk'));
    }
}
