<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Dashboard;

interface DashboardWidgetContract
{
    public function name(): string;
    public function label(): string;
    public function data(): array;
}
