<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Query;

use BlackParadise\CoreAdmin\Domain\Query\Criteria;
use BlackParadise\CoreAdmin\Domain\Query\Filter;
use BlackParadise\CoreAdmin\Domain\Query\Sort;
use PHPUnit\Framework\TestCase;

final class CriteriaTest extends TestCase
{
    public function test_constructor_uses_defaults_when_no_arguments_passed(): void
    {
        $criteria = new Criteria();

        self::assertSame([], $criteria->filters);
        self::assertSame([], $criteria->sort);
        self::assertSame(1, $criteria->page);
        self::assertSame(25, $criteria->perPage);
        self::assertNull($criteria->search);
    }

    public function test_constructor_stores_filters(): void
    {
        $filter = new Filter('status', 'active');
        $criteria = new Criteria(filters: [$filter]);

        self::assertCount(1, $criteria->filters);
        self::assertSame($filter, $criteria->filters[0]);
    }

    public function test_constructor_stores_sort(): void
    {
        $sort = new Sort('name', 'desc');
        $criteria = new Criteria(sort: [$sort]);

        self::assertCount(1, $criteria->sort);
        self::assertSame($sort, $criteria->sort[0]);
    }

    public function test_constructor_stores_page_and_per_page(): void
    {
        $criteria = new Criteria(page: 3, perPage: 50);

        self::assertSame(3, $criteria->page);
        self::assertSame(50, $criteria->perPage);
    }

    public function test_constructor_stores_search(): void
    {
        $criteria = new Criteria(search: 'john doe');

        self::assertSame('john doe', $criteria->search);
    }

    public function test_constructor_stores_multiple_filters_and_sorts(): void
    {
        $filter1 = new Filter('status', 'active');
        $filter2 = new Filter('role', 'admin');
        $sort1 = new Sort('created_at', 'desc');
        $sort2 = new Sort('name', 'asc');

        $criteria = new Criteria(
            filters: [$filter1, $filter2],
            sort: [$sort1, $sort2],
        );

        self::assertCount(2, $criteria->filters);
        self::assertCount(2, $criteria->sort);
    }
}
