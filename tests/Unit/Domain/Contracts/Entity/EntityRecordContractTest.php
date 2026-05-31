<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Contracts\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that EntityRecord correctly implements EntityRecordContract and
 * that all interface methods are accessible through the contract type hint.
 */
final class EntityRecordContractTest extends TestCase
{
    private EntityDefinitionContract $definition;

    protected function setUp(): void
    {
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('keyField')->willReturn('id');
    }

    public function test_entity_record_implements_entity_record_contract(): void
    {
        $record = new EntityRecord($this->definition);

        self::assertInstanceOf(EntityRecordContract::class, $record);
    }

    public function test_definition_accessible_through_contract(): void
    {
        $contract = $this->buildContract(['id' => 1]);

        self::assertSame($this->definition, $contract->definition());
    }

    public function test_id_accessible_through_contract(): void
    {
        $contract = $this->buildContract(['id' => 7]);

        self::assertSame(7, $contract->id());
    }

    public function test_id_returns_null_through_contract_when_key_absent(): void
    {
        $contract = $this->buildContract([]);

        self::assertNull($contract->id());
    }

    public function test_get_accessible_through_contract(): void
    {
        $contract = $this->buildContract(['email' => 'test@example.com']);

        self::assertSame('test@example.com', $contract->get('email'));
    }

    public function test_get_returns_null_through_contract_for_missing_key(): void
    {
        $contract = $this->buildContract([]);

        self::assertNull($contract->get('nonexistent'));
    }

    public function test_attributes_accessible_through_contract(): void
    {
        $attrs = ['id' => 1, 'name' => 'Bob'];
        $contract = $this->buildContract($attrs);

        self::assertSame($attrs, $contract->attributes());
    }

    public function test_relation_accessible_through_contract(): void
    {
        $relatedData = ['id' => 3, 'title' => 'Category'];
        $contract = $this->buildContractWithRelations([], ['category' => $relatedData]);

        self::assertSame($relatedData, $contract->relation('category'));
    }

    public function test_relation_returns_null_through_contract_for_missing_key(): void
    {
        $contract = $this->buildContract([]);

        self::assertNull($contract->relation('nonexistent'));
    }

    public function test_to_array_accessible_through_contract(): void
    {
        $attrs = ['id' => 5, 'status' => 'active'];
        $contract = $this->buildContract($attrs);

        self::assertSame($attrs, $contract->toArray());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildContract(array $attributes): EntityRecordContract
    {
        return new EntityRecord($this->definition, $attributes);
    }

    private function buildContractWithRelations(array $attributes, array $relations): EntityRecordContract
    {
        return new EntityRecord($this->definition, $attributes, $relations);
    }
}
