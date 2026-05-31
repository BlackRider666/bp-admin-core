<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Query;

use BlackParadise\CoreAdmin\Domain\Query\PaginatedResult;
use PHPUnit\Framework\TestCase;

final class PaginatedResultTest extends TestCase
{
    public function test_last_page_calculated_correctly(): void
    {
        $result = new PaginatedResult(items: [], total: 47, page: 1, perPage: 15);
        self::assertSame(4, $result->lastPage());
    }

    public function test_last_page_is_one_when_total_fits_in_single_page(): void
    {
        $result = new PaginatedResult(items: [], total: 10, page: 1, perPage: 15);
        self::assertSame(1, $result->lastPage());
    }

    public function test_has_pages_true_when_multiple_pages(): void
    {
        $result = new PaginatedResult(items: [], total: 30, page: 1, perPage: 15);
        self::assertTrue($result->hasPages());
    }

    public function test_has_pages_false_when_single_page(): void
    {
        $result = new PaginatedResult(items: [], total: 5, page: 1, perPage: 15);
        self::assertFalse($result->hasPages());
    }

    public function test_last_page_is_one_when_total_is_zero(): void
    {
        $result = new PaginatedResult(items: [], total: 0, page: 1, perPage: 15);
        self::assertSame(1, $result->lastPage());
    }

    public function test_last_page_is_one_when_per_page_is_zero(): void
    {
        $result = new PaginatedResult(items: [], total: 100, page: 1, perPage: 0);
        self::assertSame(1, $result->lastPage());
    }
}
