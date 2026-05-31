<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\RelationFieldTypes;
use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;
use DomainException;

final class EntityRecord implements EntityRecordContract
{
    public function __construct(
        private readonly EntityDefinitionContract $definition,
        /** @var array<string, mixed> */
        private array $attributes = [],
        /** @var array<string, mixed>*/
        private array $relations = [],
    ) {}

    public function definition(): EntityDefinitionContract
    {
        return $this->definition;
    }

    public function id(): mixed
    {
        return $this->attributes[$this->definition->keyField()] ?? null;
    }

    public function get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function relation(string $key): mixed
    {
        return $this->relations[$key] ?? null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function getByPath(string $path): mixed
    {
        if (!str_contains($path, '.')) {
            return $this->get($path);
        }

        $segments = explode('.', $path);
        $head     = array_shift($segments);

        $current = $this->relations[$head] ?? null;
        if ($current === null) {
            return null;
        }

        foreach ($segments as $segment) {
            if (is_array($current)) {
                $current = $current[$segment] ?? null;
            } elseif ($current instanceof EntityRecordContract) {
                $current = $current->getByPath($segment);
            } elseif (is_object($current)) {
                $current = $current->{$segment} ?? null;
            } else {
                return null;
            }

            if ($current === null) {
                return null;
            }
        }

        return $current;
    }

    public function setField(FieldName $name, mixed $value): void
    {
        $field = $this->resolveField($name);

        if (RelationFieldTypes::isSideEffect($field->type())) {
            throw new DomainException(sprintf(
                'Cannot setField on side-effect relation "%s" (type %s); use setRelation instead.',
                (string) $name,
                $field->type(),
            ));
        }

        $this->attributes[(string) $name] = $value;
    }

    public function setRelation(FieldName $name, mixed $value): void
    {
        $field = $this->resolveField($name);

        if (!RelationFieldTypes::isRelation($field->type())) {
            throw new DomainException(sprintf(
                'Field "%s" (type %s) is not a relation; use setField instead.',
                (string) $name,
                $field->type(),
            ));
        }

        $this->relations[(string) $name] = $value;
    }

    public function getField(FieldName $name): mixed
    {
        return $this->attributes[(string) $name] ?? null;
    }

    public function hasField(FieldName $name): bool
    {
        return array_key_exists((string) $name, $this->attributes);
    }

    private function resolveField(FieldName $name): FieldContract
    {
        foreach ($this->definition->fields() as $field) {
            if ($field->name() === (string) $name) {
                return $field;
            }
        }

        throw new DomainException(sprintf(
            'Unknown field "%s" on entity "%s".',
            (string) $name,
            $this->definition->name(),
        ));
    }
}
