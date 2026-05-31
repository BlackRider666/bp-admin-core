<?php

declare(strict_types=1);

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

/*
 * Architecture rule: Field-класи (Domain/Fields/) — final, мають
 * static `make(): self` та `type(): string` методи (enforced via FieldContract).
 *
 * AbstractField, AbstractRelationField — explicit extension points
 * у Domain/Fields/Base/, тому виключені з final-rule.
 * Concerns/HasFileAllowlist — trait, не клас.
 */

arch('concrete field classes are final')
    ->expect('BlackParadise\CoreAdmin\Domain\Fields')
    ->classes()
    ->toBeFinal()
    ->ignoring([
        AbstractField::class,
        AbstractRelationField::class,
    ]);
