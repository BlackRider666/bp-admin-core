<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;
use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;

final class BelongsToField extends AbstractRelationField
{
    public static function make(string $name, string $target): self
    {
        return new self($name, 'belongsTo', $target);
    }

    public function type(): string
    {
        return 'belongs_to';
    }

    /**
     * Emits `nullable` (suppressed when required()) and a framework-free
     * `relation_exists:<Model>` marker. Laravel adapters rewrite this marker
     * to the native `exists:table,key` rule at the infrastructure layer.
     *
     * {@inheritdoc}
     */
    protected function typeRules(): array
    {
        return [Rule::Nullable, new ParameterizedRule('relation_exists', $this->target)];
    }
}
