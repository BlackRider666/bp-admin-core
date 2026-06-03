<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;

final class TextField extends AbstractField
{
    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'text';
    }

    /**
     * Emits `max:<n>` when {@see maxLength()} has been set.
     *
     * {@inheritdoc}
     */
    protected function typeRules(): array
    {
        if ($this->maxLengthValue !== null) {
            return [new ParameterizedRule('max', (string) $this->maxLengthValue)];
        }

        return [];
    }
}
