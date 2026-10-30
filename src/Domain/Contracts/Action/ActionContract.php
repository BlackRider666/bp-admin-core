<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Action;

use BlackParadise\CoreAdmin\Domain\Action\ActionContext;
use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;

interface ActionContract
{
    public function name(): string;
    public function label(): string;

    /**
     * @return list<ActionScope>
     */
    public function scopes(): array;
    public function permission(): ?string;
    public function confirm(): bool;
    public function meta(): array;

    /**
     * Input fields collected (and validated) before handle() runs.
     *
     * @return array<FieldContract>
     */
    public function fields(): array;

    public function handle(ActionContext $context): ActionResult;
}
