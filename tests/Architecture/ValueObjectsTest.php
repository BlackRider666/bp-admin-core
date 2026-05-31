<?php

declare(strict_types=1);

/*
 * Architecture rule: Value Objects у Domain/ValueObjects/ — final readonly classes.
 */

arch('value objects are final')
    ->expect('BlackParadise\CoreAdmin\Domain\ValueObjects')
    ->classes()
    ->toBeFinal();

arch('value objects are readonly')
    ->expect('BlackParadise\CoreAdmin\Domain\ValueObjects')
    ->classes()
    ->toBeReadonly();
