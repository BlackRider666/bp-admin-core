<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\ValueObjects;

enum Capability: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
