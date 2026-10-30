<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Action;

interface ActionResolverContract
{
    /** @param class-string<ActionContract>|string $actionClass */
    public function resolve(string $actionClass): ActionContract;
}
