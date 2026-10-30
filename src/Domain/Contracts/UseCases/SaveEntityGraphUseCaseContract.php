<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\UseCases;

use BlackParadise\CoreAdmin\Domain\Commands\SaveEntityGraphCommand;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;

interface SaveEntityGraphUseCaseContract
{
    public function execute(SaveEntityGraphCommand $command): EntityRecordContract;
}
