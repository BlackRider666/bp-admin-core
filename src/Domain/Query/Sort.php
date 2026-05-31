<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Query;

use InvalidArgumentException;

final readonly class Sort
{
    public function __construct(
        public string $field,
        public string $direction = 'asc',
    ) {
        if (!in_array(strtolower($this->direction), ['asc', 'desc'], true)) {
            throw new InvalidArgumentException(
                "Sort direction must be 'asc' or 'desc', got '{$this->direction}'.",
            );
        }
    }
}
