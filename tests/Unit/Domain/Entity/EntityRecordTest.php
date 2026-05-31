<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use PHPUnit\Framework\TestCase;

final class EntityRecordTest extends TestCase
{
    private EntityDefinitionContract $definition;

    protected function setUp(): void
    {
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('keyField')->willReturn('id');
    }

    public function test_definition_returns_the_injected_definition(): void
    {
        $record = new EntityRecord($this->definition);

        self::assertSame($this->definition, $record->definition());
    }

    public function test_id_returns_value_from_key_field_attribute(): void
    {
        $record = new EntityRecord($this->definition, ['id' => 42, 'name' => 'John']);

        self::assertSame(42, $record->id());
    }

    public function test_id_returns_null_when_key_field_not_in_attributes(): void
    {
        $record = new EntityRecord($this->definition, ['name' => 'John']);

        self::assertNull($record->id());
    }

    public function test_get_returns_attribute_value_by_key(): void
    {
        $record = new EntityRecord($this->definition, ['email' => 'john@example.com']);

        self::assertSame('john@example.com', $record->get('email'));
    }

    public function test_get_returns_null_when_attribute_not_present(): void
    {
        $record = new EntityRecord($this->definition, []);

        self::assertNull($record->get('missing_key'));
    }

    public function test_attributes_returns_all_attributes(): void
    {
        $attrs = ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'];
        $record = new EntityRecord($this->definition, $attrs);

        self::assertSame($attrs, $record->attributes());
    }

    public function test_attributes_returns_empty_array_when_no_attributes(): void
    {
        $record = new EntityRecord($this->definition);

        self::assertSame([], $record->attributes());
    }

    public function test_relation_returns_relation_value_by_key(): void
    {
        $relatedData = ['id' => 5, 'title' => 'Post'];
        $record = new EntityRecord($this->definition, [], ['post' => $relatedData]);

        self::assertSame($relatedData, $record->relation('post'));
    }

    public function test_relation_returns_null_when_relation_not_present(): void
    {
        $record = new EntityRecord($this->definition, [], []);

        self::assertNull($record->relation('missing_relation'));
    }

    public function test_to_array_returns_attributes(): void
    {
        $attrs = ['id' => 1, 'name' => 'Bob'];
        $record = new EntityRecord($this->definition, $attrs);

        self::assertSame($attrs, $record->toArray());
    }

    public function test_id_uses_custom_key_field(): void
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('keyField')->willReturn('uuid');

        $record = new EntityRecord($definition, ['uuid' => 'abc-123', 'name' => 'Test']);

        self::assertSame('abc-123', $record->id());
    }
}
