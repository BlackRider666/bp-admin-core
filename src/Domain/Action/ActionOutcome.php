<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Action;

enum ActionOutcome: string
{
    case Success = 'success';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
