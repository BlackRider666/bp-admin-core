<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts;

interface ValueHasherContract
{
    public function hash(string $value): string;
}
