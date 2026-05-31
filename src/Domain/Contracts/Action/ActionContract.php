<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Action;

interface ActionContract
{
    public function name(): string;
    public function label(): string;
    public function scope(): string;
    public function permission(): ?string;
    public function confirm(): bool;
    public function meta(): array;
}
