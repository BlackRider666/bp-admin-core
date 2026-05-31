<?php

declare(strict_types=1);

/*
 * Architecture rule: усе в Domain/Contracts/ — interface (PHP `interface`).
 */

arch('domain contracts are interfaces')
    ->expect('BlackParadise\CoreAdmin\Domain\Contracts')
    ->toBeInterfaces();
