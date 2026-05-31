<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class EntityKey implements Stringable
{
    public function __construct(
        public int|string $value,
        public string $type = 'int',
    ) {
        if ($type !== 'int' && $type !== 'string') {
            throw new InvalidArgumentException("EntityKey type must be 'int' or 'string', got '{$type}'.");
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value && $this->type === $other->type;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
