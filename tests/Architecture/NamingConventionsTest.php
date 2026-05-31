<?php

declare(strict_types=1);

use BlackParadise\CoreAdmin\Application\UseCases\Entity\RuleBuilder;

/*
 * Architecture rule: naming conventions per spec §4.4.
 *
 * - Use Cases у Application/UseCases/ закінчуються на `UseCase`.
 * - Domain контракти в Contracts/ — interfaces (детально перевіряється
 *   у ContractInterfaceTest).
 *
 * RuleBuilder — utility-class у UseCases/Entity/ (не sам use case);
 * TODO: перенести у Application/Builders/ окремою сесією.
 */

arch('use cases end with "UseCase"')
    ->expect('BlackParadise\CoreAdmin\Application\UseCases')
    ->classes()
    ->toHaveSuffix('UseCase')
    ->ignoring([
        RuleBuilder::class,
    ]);
