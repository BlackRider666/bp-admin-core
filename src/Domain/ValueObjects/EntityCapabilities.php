<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\ValueObjects;

final readonly class EntityCapabilities
{
    public function __construct(
        public bool $create = true,
        public bool $update = true,
        public bool $delete = true,
    ) {}

    public static function all(): self
    {
        return new self(true, true, true);
    }

    public static function readOnly(): self
    {
        return new self(false, false, false);
    }

    public function without(Capability $capability): self
    {
        return new self(
            create: $capability === Capability::Create ? false : $this->create,
            update: $capability === Capability::Update ? false : $this->update,
            delete: $capability === Capability::Delete ? false : $this->delete,
        );
    }

    public function allows(Capability $capability): bool
    {
        return match ($capability) {
            Capability::Create => $this->create,
            Capability::Update => $this->update,
            Capability::Delete => $this->delete,
        };
    }
}
