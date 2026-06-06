<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

final class MorphToField extends AbstractRelationField
{
    /** @var array<string, array{label: string, display: string}> */
    private array $morphTypeMap = [];

    public static function make(string $name, string $target): self
    {
        return new self($name, 'morphTo', $target);
    }

    public function type(): string
    {
        return 'morph_to';
    }

    /**
     * Declare the polymorphic targets the picker may choose from.
     *
     * Each entry: FQCN => 'Label'  OR  FQCN => ['label' => ..., 'display' => column].
     * `display` defaults to the field's displayField().
     *
     * @param array<string, string|array{label?: string, display?: string}> $types
     */
    public function morphTypes(array $types): static
    {
        $map = [];
        foreach ($types as $class => $config) {
            if (is_string($config)) {
                $map[$class] = ['label' => $config, 'display' => $this->displayField()];
                continue;
            }
            $map[$class] = [
                'label'   => $config['label'] ?? $class,
                'display' => $config['display'] ?? $this->displayField(),
            ];
        }
        $this->morphTypeMap = $map;

        return $this;
    }

    /** @return array<string, array{label: string, display: string}> */
    public function morphTypeMap(): array
    {
        return $this->morphTypeMap;
    }

    public function typeColumn(): string
    {
        return $this->name() . '_type';
    }

    public function idColumn(): string
    {
        return $this->name() . '_id';
    }

    /** @return list<string> */
    public function morphColumns(): array
    {
        return [$this->typeColumn(), $this->idColumn()];
    }
}
