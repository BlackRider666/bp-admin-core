<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use BlackParadise\CoreAdmin\Domain\ValueObjects\Capability;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/** A29 — capabilities VO (create/update/delete). */
final class EntityCapabilitiesTest extends TestCase
{
    public function test_capability_enum_has_create_update_delete_cases_with_string_values(): void
    {
        self::assertTrue(enum_exists(Capability::class), 'Capability enum must exist.');
        self::assertSame('create', Capability::Create->value);
        self::assertSame('update', Capability::Update->value);
        self::assertSame('delete', Capability::Delete->value);
        self::assertCount(3, Capability::cases());
    }

    public function test_all_allows_every_capability(): void
    {
        $caps = EntityCapabilities::all();

        self::assertTrue($caps->create);
        self::assertTrue($caps->update);
        self::assertTrue($caps->delete);
    }

    public function test_read_only_denies_every_capability(): void
    {
        $caps = EntityCapabilities::readOnly();

        self::assertFalse($caps->create);
        self::assertFalse($caps->update);
        self::assertFalse($caps->delete);
    }

    public function test_without_removes_only_the_given_capability(): void
    {
        $caps = EntityCapabilities::all()->without(Capability::Delete);

        self::assertTrue($caps->create);
        self::assertTrue($caps->update);
        self::assertFalse($caps->delete);
    }

    public function test_without_is_chainable_and_cumulative(): void
    {
        $caps = EntityCapabilities::all()->without(Capability::Create)->without(Capability::Update);

        self::assertFalse($caps->create);
        self::assertFalse($caps->update);
        self::assertTrue($caps->delete);
    }

    public function test_without_returns_new_instance_and_leaves_original_unchanged(): void
    {
        $original = EntityCapabilities::all();

        $changed = $original->without(Capability::Create);

        self::assertNotSame($original, $changed);
        self::assertTrue($original->create);
    }

    public function test_allows_reports_state_per_capability(): void
    {
        $caps = EntityCapabilities::all()->without(Capability::Update);

        self::assertTrue($caps->allows(Capability::Create));
        self::assertFalse($caps->allows(Capability::Update));
        self::assertTrue($caps->allows(Capability::Delete));
    }

    public function test_class_is_final_readonly(): void
    {
        $ref = new ReflectionClass(EntityCapabilities::class);

        self::assertTrue($ref->isFinal());
        self::assertTrue($ref->isReadOnly());
    }
}
