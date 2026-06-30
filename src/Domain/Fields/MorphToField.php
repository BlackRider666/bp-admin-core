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

    public function kind(): RelationKind
    {
        return RelationKind::MorphTo;
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

    /**
     * Expand to two synthetic column keys regardless of $locales.
     * MorphToField is not locale-aware; $locales is ignored.
     *
     * In 'create' context both columns carry 'required'.
     * In any other context ('update') both columns carry 'nullable'.
     *
     * Exact marker strings (pinned — consumed by the Laravel adapter in Task 1.3):
     *   'morph_type_in:<FQCN1>,<FQCN2>,...'  — on the _type column
     *   'morph_exists'                         — on the _id column
     *
     * NOTE: presence (required on create / nullable on update) is derived from
     * $context BY DESIGN. The field's own ruleSet() — e.g. ->required()/->nullable()
     * set on the MorphToField — is intentionally NOT forwarded to the two synthetic
     * _type/_id columns, so an "optional on create" morphTo is not currently
     * expressible. Revisit only if that need arises (it would require deciding which
     * of the _type/_id columns carries the field's own rules).
     *
     * @param array<string> $locales Ignored — MorphToField is not locale-aware.
     * @return array<string, list<string>>
     */
    public function expandRules(array $locales, string $context): array
    {
        $presence     = $context === 'create' ? 'required' : 'nullable';
        $fqcns        = implode(',', array_keys($this->morphTypeMap));
        $morphTypeIn  = 'morph_type_in:' . $fqcns;

        return [
            $this->typeColumn() => [$presence, $morphTypeIn],
            $this->idColumn()   => [$presence, 'morph_exists'],
        ];
    }
}
