<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Query;

final readonly class Criteria
{
    /**
     * @param array<Filter> $filters
     * @param array<Sort> $sort
     */
    public function __construct(
        public array $filters = [],
        public array $sort = [],
        public int $page = 1,
        public int $perPage = 25,
        public ?string $search = null,
    ) {}
}
