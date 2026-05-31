<?php

declare(strict_types=1);

/*
 * Architecture rule: Domain і Application шари НЕ імпортують Infrastructure
 * чи будь-який framework (Illuminate, Symfony, Laravel). bp-admin-core є
 * pure-PHP ядром — це інваріант, який ламає zero-Laravel-deps мета.
 */

arch('domain does not depend on application or any framework')
    ->expect('BlackParadise\CoreAdmin\Domain')
    ->not->toUse([
        'BlackParadise\CoreAdmin\Application',
        'Illuminate',
        'Symfony',
        'Laravel',
    ]);

arch('application does not depend on any framework')
    ->expect('BlackParadise\CoreAdmin\Application')
    ->not->toUse([
        'Illuminate',
        'Symfony',
        'Laravel',
    ]);
