<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;
use DomainException;

interface EntityRecordContract
{
    public function definition(): EntityDefinitionContract;

    public function id(): mixed;

    public function get(string $key): mixed;

    public function attributes(): array;

    public function relation(string $key): mixed;

    public function toArray(): array;

    /**
     * Resolve a dot-path: if path contains dots, walk through relations;
     * otherwise falls back to get($key).
     */
    public function getByPath(string $path): mixed;

    /**
     * Set a column-attribute by FieldName. Structural invariant:
     * field must exist in $this->definition()->fields() and must NOT
     * be a side-effect relation type (belongs_to_many, has_many,
     * has_one, morph_many, morph_file).
     *
     * @throws DomainException якщо поле не існує або є relation-типом
     */
    public function setField(FieldName $name, mixed $value): void;

    /**
     * Set a relation by FieldName. Structural invariant:
     * field must exist in $this->definition()->fields() AND
     * must be a relation type (belongs_to, belongs_to_many, has_many,
     * has_one, morph_to, morph_many, morph_file).
     *
     * @throws DomainException якщо поле не існує або не є relation-типом
     */
    public function setRelation(FieldName $name, mixed $value): void;

    /**
     * Typed accessor — повертає значення column-атрибута за FieldName,
     * або null якщо не задане.
     */
    public function getField(FieldName $name): mixed;

    /**
     * Чи присутнє поле з даним FieldName серед attributes (column data).
     */
    public function hasField(FieldName $name): bool;
}
