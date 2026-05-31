<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Query;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;

final readonly class PaginatedResult
{
    /**
     * @param array<EntityRecordContract> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        if ($this->total === 0 || $this->perPage <= 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->perPage);
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }
}
