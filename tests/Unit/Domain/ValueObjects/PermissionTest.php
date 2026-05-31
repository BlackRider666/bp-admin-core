<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use BlackParadise\CoreAdmin\Domain\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    public function test_constructor_stores_value(): void
    {
        $permission = new Permission('admin.users.create');

        self::assertSame('admin.users.create', $permission->value);
    }

    public function test_equals_returns_true_when_values_match(): void
    {
        $a = new Permission('admin.users.list');
        $b = new Permission('admin.users.list');

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_when_values_differ(): void
    {
        $a = new Permission('admin.users.create');
        $b = new Permission('admin.users.delete');

        self::assertFalse($a->equals($b));
    }

    public function test_to_string_returns_value(): void
    {
        $permission = new Permission('admin.posts.view');

        self::assertSame('admin.posts.view', (string) $permission);
    }
}
