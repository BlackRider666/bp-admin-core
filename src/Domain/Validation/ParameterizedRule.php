<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Validation;

final readonly class ParameterizedRule
{
    public function __construct(
        public string $name,
        public mixed $value,
    ) {}
}
