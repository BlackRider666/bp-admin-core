<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields\Base;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\RelationFieldContract;
use Closure;
use InvalidArgumentException;

abstract class AbstractRelationField extends AbstractField implements RelationFieldContract
{
    protected ?string $explicitRelationName = null;
    protected string $displayFieldName = 'name';
    protected ?string $embeddedDefinitionClass = null;
    protected bool    $owned                   = false;
    /** @var array<string, mixed> */
    protected array $state = [];
    /** @var list<array{column: string, value: mixed}> */
    protected array $optionConstraintsList = [];

    /** @var (Closure(array<string, mixed>): string)|null */
    protected ?Closure $displayUsing = null;

    /** @var list<string> */
    protected array $displayEagerLoadList = [];

    protected ?string $displayOrderColumn = null;

    /**
     * @param bool $multiple Reserved for presenter usage — signals to the UI
     *                       layer that the field should render as a multi-select. NOT consumed by
     *                       RelationWriter or the persistence layer; cardinality of the actual
     *                       write is determined by `$relationKind` (belongsToMany, hasMany,
     *                       morphMany). Setting `multiple(true)` on a `belongsTo` will not turn
     *                       the relation into a many-to-many.
     */
    public function __construct(
        string $name,
        protected string $relationKind,
        protected string $target,
        protected bool $multiple = false,
        protected bool $createInline = false,
        ?string $label = null,
        array $rules = [],
        bool $visibleOnList = true,
        bool $visibleOnForm = true,
        bool $visibleOnShow = true,
        bool $sortable = false,
        bool $filterable = false,
        array $meta = [],
    ) {
        // Guard against degenerate FK names — a belongsTo field literally named
        // "_id" yields an empty relation name via substr() and breaks downstream
        // eager-load / introspection. Fail fast at construction.
        if (
            $relationKind === 'belongsTo'
            && str_ends_with($name, '_id')
            && strlen($name) <= 3
        ) {
            throw new InvalidArgumentException(sprintf(
                "BelongsTo FK column name '%s' is too short — the part before "
                . "'_id' would be empty. Use a name like 'author_id'.",
                $name,
            ));
        }

        parent::__construct($name, $label, $rules, $visibleOnList, $visibleOnForm, $visibleOnShow, $sortable, $filterable, $meta);
    }

    public function label(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }
        // belongsTo with FK column name (e.g. "genre_id") — humanise relation name ("Genre")
        if ($this->relationKind === 'belongsTo' && str_ends_with($this->name, '_id')) {
            return ucfirst(str_replace('_', ' ', $this->relationName()));
        }
        return parent::label();
    }

    public function relationKind(): string
    {
        return $this->relationKind;
    }
    public function target(): string
    {
        return $this->target;
    }
    /**
     * Reserved for presenter usage — signals to the UI layer that the field
     * should render as a multi-select. NOT consumed by RelationWriter or the
     * persistence layer; the write cardinality is determined by relationKind().
     */
    public function multiple(): bool
    {
        return $this->multiple;
    }
    public function createInline(): bool
    {
        return $this->createInline;
    }

    public function relationName(): string
    {
        if ($this->explicitRelationName !== null) {
            return $this->explicitRelationName;
        }

        if ($this->relationKind === 'belongsTo') {
            $fieldName = $this->name();
            if (str_ends_with($fieldName, '_id')) {
                return substr($fieldName, 0, -3);
            }
        }

        return $this->name();
    }

    public function displayField(): string
    {
        return $this->displayFieldName;
    }

    public function withRelationName(string $name): static
    {
        $this->explicitRelationName = $name;
        return $this;
    }

    public function withDisplayField(string $field): static
    {
        $this->displayFieldName = $field;
        return $this;
    }

    /**
     * Mark this relation as an inline-embedded CRUD surface. The embedded
     * entity's fields appear within the host form.
     *
     * @param string $embeddedDefinitionClass Fully-qualified EntityDefinition class.
     * @throws InvalidArgumentException If the class does not exist or is not an EntityDefinitionContract.
     */
    public function embed(string $embeddedDefinitionClass): static
    {
        if (!class_exists($embeddedDefinitionClass)) {
            throw new InvalidArgumentException(
                "Embedded definition class \"{$embeddedDefinitionClass}\" does not exist.",
            );
        }
        if (!is_subclass_of(
            $embeddedDefinitionClass,
            EntityDefinitionContract::class,
        )) {
            throw new InvalidArgumentException(
                "Embedded definition \"{$embeddedDefinitionClass}\" must extend EntityDefinitionContract.",
            );
        }
        $this->embeddedDefinitionClass = $embeddedDefinitionClass;
        return $this;
    }

    public function isEmbedded(): bool
    {
        return $this->embeddedDefinitionClass !== null;
    }

    public function embeddedDefinition(): ?string
    {
        return $this->embeddedDefinitionClass;
    }

    /**
     * Mark the related record as owned by the host — delete-propagates on
     * host->delete. Orthogonal to embed(): can be used alone.
     */
    public function owns(): static
    {
        $this->owned = true;
        return $this;
    }

    public function isOwned(): bool
    {
        return $this->owned;
    }

    /**
     * Fixed attributes always written to the embedded record on create/update,
     * regardless of the submitted payload (e.g. a supertype/subtype discriminator
     * column). State values win over payload values.
     *
     * Do NOT put the embedded record's primary key here: state sets column
     * values only and must not carry the PK used by update/merge resolution.
     *
     * @param array<string, mixed> $state
     */
    public function withState(array $state): static
    {
        $this->state = $state;

        return $this;
    }

    /** @return array<string, mixed> */
    public function state(): array
    {
        return $this->state;
    }

    /**
     * Constrain the option list to rows where $column equals $value.
     *
     * Multiple calls accumulate constraints (AND semantics). The infrastructure
     * provider (e.g. EloquentRelationOptionsProvider) reads these via
     * {@see optionConstraints()} and applies them as WHERE clauses.
     *
     * Example:
     *   BelongsToField::make('city_id', City::class)->whereOption('country_id', 1)
     */
    public function whereOption(string $column, mixed $value): static
    {
        $this->optionConstraintsList[] = ['column' => $column, 'value' => $value];

        return $this;
    }

    /**
     * Batch version of {@see whereOption()} — accepts an associative array of
     * column => value pairs. All constraints are appended (AND semantics).
     *
     * Example:
     *   BelongsToManyField::make('tags', Tag::class)->scopeOptions(['type' => 'article', 'active' => true])
     *
     * @param array<string, mixed> $constraints
     */
    public function scopeOptions(array $constraints): static
    {
        foreach ($constraints as $column => $value) {
            $this->whereOption($column, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return list<array{column: string, value: mixed}>
     */
    public function optionConstraints(): array
    {
        return $this->optionConstraintsList;
    }

    /**
     * Обчислювана мітка relation-селекта. Closure отримує асоціативний масив
     * рядка цілі (атрибути + eager-loaded relation як вкладені масиви) і повертає
     * рядок-мітку. Framework-free: closure типу array->string.
     *
     * @param Closure(array<string, mixed>): string $resolver
     */
    public function withDisplayUsing(Closure $resolver): static
    {
        $this->displayUsing = $resolver;
        return $this;
    }

    /**
     * Relation'и, які провайдер/репозиторій мають eager-load'ити, щоб closure
     * мав потрібні дані (без N+1). Напр. ['history'].
     *
     * @param array<string> $relations
     */
    public function withDisplayEagerLoad(array $relations): static
    {
        $this->displayEagerLoadList = array_values($relations);
        return $this;
    }

    /**
     * Реальна колонка для ORDER BY у списку опцій. Якщо null — провайдер сортує
     * у PHP за обчисленою міткою.
     */
    public function withDisplayOrderColumn(?string $column): static
    {
        $this->displayOrderColumn = $column;
        return $this;
    }

    public function hasDisplayCallback(): bool
    {
        return $this->displayUsing instanceof Closure;
    }

    /** @return Closure(array<string, mixed>): string|null */
    public function displayCallback(): ?Closure
    {
        return $this->displayUsing;
    }

    /** @return list<string> */
    public function displayEagerLoad(): array
    {
        return $this->displayEagerLoadList;
    }

    public function displayOrderColumn(): ?string
    {
        return $this->displayOrderColumn;
    }

    /**
     * Єдина точка обчислення мітки цілі. Closure якщо заданий; інакше скалярне
     * значення $fallbackField (нескалярне → порожній рядок, щоб не зламати
     * translatable-рендер у blade, який іде окремим шляхом).
     *
     * @param array<string, mixed> $row
     */
    public function resolveDisplayLabel(array $row, string $fallbackField): string
    {
        if ($this->displayUsing instanceof Closure) {
            return ($this->displayUsing)($row);
        }
        $value = $row[$fallbackField] ?? '';
        return is_scalar($value) ? (string) $value : '';
    }
}
