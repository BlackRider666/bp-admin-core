<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use PHPUnit\Framework\TestCase;

final class BelongsToManyFieldPivotTest extends TestCase
{
    public function test_pivot_data_defaults_to_empty_array(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag');

        self::assertSame([], $field->getPivotData());
    }

    public function test_with_pivot_data_stores_static_payload(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->withPivotData(['approved' => true, 'order' => 1]);

        self::assertSame(['approved' => true, 'order' => 1], $field->getPivotData());
    }

    public function test_with_pivot_data_returns_same_instance_for_chaining(): void
    {
        $field  = BelongsToManyField::make('tags', 'App\\Models\\Tag');
        $result = $field->withPivotData(['approved' => true]);

        self::assertSame($field, $result);
    }

    public function test_pivot_payload_callback_is_null_by_default(): void
    {
        $field = BelongsToManyField::make('tags', 'App\\Models\\Tag');

        self::assertNull($field->getPivotPayloadCallback());
    }

    public function test_pivot_payload_stores_callback(): void
    {
        $callback = fn(int $id, array $hostAttrs): array => ['approved' => $id > 0];
        $field    = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->pivotPayload($callback);

        self::assertSame($callback, $field->getPivotPayloadCallback());
    }

    public function test_pivot_payload_returns_same_instance(): void
    {
        $field  = BelongsToManyField::make('tags', 'App\\Models\\Tag');
        $result = $field->pivotPayload(fn($id, $host): array => []);

        self::assertSame($field, $result);
    }

    public function test_with_pivot_data_and_pivot_payload_can_coexist(): void
    {
        $callback = fn(int $id, array $hostAttrs): array => ['custom' => true];
        $field    = BelongsToManyField::make('tags', 'App\\Models\\Tag')
            ->withPivotData(['approved' => false])
            ->pivotPayload($callback);

        self::assertSame(['approved' => false], $field->getPivotData());
        self::assertSame($callback, $field->getPivotPayloadCallback());
    }
}
