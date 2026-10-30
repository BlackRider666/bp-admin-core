<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Action;

enum ActionScope: string
{
    case Row = 'row';
    case Bulk = 'bulk';
    case Entity = 'entity';
}
