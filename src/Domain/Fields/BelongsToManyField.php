<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

final class BelongsToManyField extends AbstractRelationField
{
    /** @var array<string, mixed> */
    private array $pivotData = [];

    /** @var (callable(int|string, array<string, mixed>): array<string, mixed>)|null */
    private $pivotPayloadCallback;

    public static function make(string $name, string $target): self
    {
        return new self($name, 'belongsToMany', $target);
    }

    public function type(): string
    {
        return 'belongs_to_many';
    }

    /**
     * Статичні pivot-колонки, що застосовуються до всіх attached records.
     * Приклад: `->withPivotData(['approved' => true])`.
     *
     * @param array<string, mixed> $data
     */
    public function withPivotData(array $data): self
    {
        $this->pivotData = $data;
        return $this;
    }

    /** @return array<string, mixed> */
    public function getPivotData(): array
    {
        return $this->pivotData;
    }

    /**
     * Динамічний pivot-payload — callback отримує id пов'язаної сутності та
     * attributes host-запису, повертає pivot-масив.
     *
     * Приклад:
     *   ->pivotPayload(fn($id, $hostAttrs) => ['approved' => $hostAttrs['auto_approve']])
     *
     * @param callable(int|string, array<string, mixed>): array<string, mixed> $callback
     */
    public function pivotPayload(callable $callback): self
    {
        $this->pivotPayloadCallback = $callback;
        return $this;
    }

    /**
     * @return (callable(int|string, array<string, mixed>): array<string, mixed>)|null
     */
    public function getPivotPayloadCallback(): ?callable
    {
        return $this->pivotPayloadCallback;
    }
}
