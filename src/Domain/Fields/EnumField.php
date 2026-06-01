<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BackedEnum;
use BlackParadise\CoreAdmin\Domain\Contracts\LabeledValueContract;
use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;
use InvalidArgumentException;

/**
 * Renders a fixed-option select input driven by a PHP enum or explicit value map.
 *
 * Security note: EnumField receives `$class` ONLY from EntityDefinition source code
 * (developer-controlled). Never from user input. The `class_exists($class)` autoload-
 * trigger inside resolveOptionsFromClass() is therefore not exploitable by attackers.
 */
final class EnumField extends AbstractField
{
    /** @var array<int|string, string> */
    private readonly array $options;

    private bool $multiple = false;

    /**
     * @param array<int|string, string>|string $optionsOrSource
     *                                                          - array: legacy explicit value=>label map.
     *                                                          - string: class-string of BackedEnum or LabeledValueContract implementor.
     */
    public function __construct(
        string $name,
        array|string $optionsOrSource = [],
        ?string $label = null,
        array $rules = [],
        bool $visibleOnList = true,
        bool $visibleOnForm = true,
        bool $visibleOnShow = true,
        bool $sortable = false,
        bool $filterable = false,
        array $meta = [],
    ) {
        parent::__construct($name, $label, $rules, $visibleOnList, $visibleOnForm, $visibleOnShow, $sortable, $filterable, $meta);

        $this->options = is_array($optionsOrSource)
            ? $optionsOrSource
            : $this->resolveOptionsFromClass($optionsOrSource);
    }

    public static function make(string $name, array|string $optionsOrSource = []): self
    {
        return new self($name, $optionsOrSource);
    }

    public function type(): string
    {
        return 'enum';
    }

    /** @return array<int|string, string> */
    public function options(): array
    {
        return $this->options;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        if ($multiple) {
            $this->ruleSetInstance->add(Rule::Array);
        } else {
            $this->ruleSetInstance->remove(Rule::Array);
        }

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'options'  => $this->options,
            'multiple' => $this->multiple,
        ]);
    }

    /**
     * @return array<int|string, string>
     *
     * @throws InvalidArgumentException when the class is neither BackedEnum
     *                                  nor LabeledValueContract implementor.
     */
    private function resolveOptionsFromClass(string $class): array
    {
        if (!class_exists($class) && !enum_exists($class)) {
            throw new InvalidArgumentException(
                "EnumField: '{$class}' is not a valid options source — expected array, BackedEnum, or LabeledValueContract class.",
            );
        }

        $isBackedEnum   = is_subclass_of($class, BackedEnum::class);
        $isLabeledValue = is_subclass_of($class, LabeledValueContract::class);

        if ($isBackedEnum) {
            $result = [];
            foreach ($class::cases() as $case) {
                /** @var BackedEnum $case */
                $result[$case->value] = $case instanceof LabeledValueContract
                    ? $case->label()
                    : $case->name;
            }
            return $result;
        }

        if ($isLabeledValue) {
            return $class::options();
        }

        throw new InvalidArgumentException(
            "EnumField: '{$class}' must be either a BackedEnum or implement LabeledValueContract.",
        );
    }
}
