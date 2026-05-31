<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Query;

use BlackParadise\CoreAdmin\Domain\Query\Sort;
use PHPUnit\Framework\TestCase;

final class SortTest extends TestCase
{
    public function test_constructor_stores_field_with_default_direction_asc(): void
    {
        $sort = new Sort('created_at');

        self::assertSame('created_at', $sort->field);
        self::assertSame('asc', $sort->direction);
    }

    public function test_constructor_accepts_desc_direction(): void
    {
        $sort = new Sort('name', 'desc');

        self::assertSame('name', $sort->field);
        self::assertSame('desc', $sort->direction);
    }

    public function test_constructor_accepts_custom_direction(): void
    {
        $sort = new Sort('priority', 'asc');

        self::assertSame('asc', $sort->direction);
    }
}
