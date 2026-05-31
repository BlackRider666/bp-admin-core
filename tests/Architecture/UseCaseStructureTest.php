<?php

declare(strict_types=1);

use BlackParadise\CoreAdmin\Application\UseCases\Entity\ResolveEmbeddedRelationsUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Entity\RuleBuilder;

/*
 * Architecture rule: UseCase класи мають публічний метод `execute()`.
 * Це enforce'ить command-pattern discipline.
 *
 * Pest's arch API не має assertion "exactly one public method" — для
 * повного контролю (fluent setX/withX заборонені) покладаємося на code
 * review та /check-layers skill. Тут — guardrail на наявність execute().
 *
 * Виключення:
 * - RuleBuilder — utility-class (TODO: перенести у Application/Builders/).
 * - ResolveEmbeddedRelationsUseCase — має resolveOnStore() / resolveOnUpdate()
 *   замість execute(). TODO Phase 4+: рефакторити у service або розбити на
 *   два окремих use cases.
 */

arch('use cases have execute() method')
    ->expect('BlackParadise\CoreAdmin\Application\UseCases')
    ->classes()
    ->toHaveMethod('execute')
    ->ignoring([
        RuleBuilder::class,
        ResolveEmbeddedRelationsUseCase::class,
    ]);
