<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use PHPUnit\Framework\TestCase;

/**
 * A9 (B20): EntityRecord::getByPath('parent.child.title') returns the nested
 * relation value when intermediate segment is an EntityRecordContract relation.
 *
 * Currently BUGS: when intermediate segment is EntityRecordContract,
 * current code calls $current->getByPath($segment) but $segment at that
 * point is already the tail — however the actual bug is that
 * $current->relation($segment) is not tried first before $current->get($segment),
 * so nested EntityRecord-typed relations return null.
 * These tests are RED against current code.
 */
final class EntityRecordGetByPathTest extends TestCase
{
    private function stubDefinition(): EntityDefinitionContract
    {
        $stub = $this->createStub(EntityDefinitionContract::class);
        $stub->method('keyField')->willReturn('id');
        return $stub;
    }

    /**
     * @test
     * host.getByPath('parent.child.title') resolves through a chain of
     * EntityRecordContract intermediate records.
     *
     * Bug: when intermediate segment is EntityRecordContract, the code calls
     * $current->getByPath($segment) recursively (which is correct for the tail),
     * but fails to look up via ->relation() first — so a segment that is stored
     * as a relation (not an attribute) on the intermediate record returns null.
     */
    public function test_get_by_path_resolves_through_entity_record_contract_intermediate(): void
    {
        $def = $this->stubDefinition();

        // Inner-most record: has 'title' as plain attribute.
        $childRecord = new EntityRecord($def, ['id' => 2, 'title' => 'Nested Title']);

        // Intermediate record: 'child' is stored as a relation (EntityRecord).
        $parentRecord = new EntityRecord(
            $def,
            attributes: ['id' => 1],
            relations: ['child' => $childRecord],
        );

        // Host record: 'parent' is stored as a relation (EntityRecord).
        $host = new EntityRecord(
            $def,
            attributes: ['id' => 10],
            relations: ['parent' => $parentRecord],
        );

        self::assertSame(
            'Nested Title',
            $host->getByPath('parent.child.title'),
            'getByPath must resolve through EntityRecordContract intermediates via ->relation().',
        );
    }

    /**
     * @test
     * Two-level path works when intermediate segment is an EntityRecordContract
     * stored as a relation.
     */
    public function test_get_by_path_two_levels_through_entity_record_contract(): void
    {
        $def = $this->stubDefinition();

        // Publication record carries 'year' attribute.
        $publicationRecord = new EntityRecord($def, ['id' => 5, 'year' => 2024]);

        // Host has 'publication' as a relation (EntityRecord).
        $host = new EntityRecord(
            $def,
            attributes: ['id' => 1],
            relations: ['publication' => $publicationRecord],
        );

        self::assertSame(
            2024,
            $host->getByPath('publication.year'),
            'getByPath must return attribute from EntityRecordContract relation at one level deep.',
        );
    }

    /**
     * @test
     * Intermediate EntityRecordContract whose sub-relation is also an EntityRecord
     * (three-level depth).
     */
    public function test_get_by_path_three_levels_through_entity_record_contracts(): void
    {
        $def = $this->stubDefinition();

        // Leaf record.
        $authorRecord = new EntityRecord($def, ['id' => 3, 'name' => 'Tolkien']);

        // Mid-level: 'author' stored as relation (EntityRecord).
        $publicationRecord = new EntityRecord(
            $def,
            attributes: ['id' => 5, 'title' => 'Fellowship'],
            relations: ['author' => $authorRecord],
        );

        // Host: 'publication' stored as relation (EntityRecord).
        $host = new EntityRecord(
            $def,
            attributes: ['id' => 1],
            relations: ['publication' => $publicationRecord],
        );

        self::assertSame(
            'Tolkien',
            $host->getByPath('publication.author.name'),
            'getByPath must traverse three-level EntityRecord chain correctly.',
        );
    }

    /**
     * @test
     * When the intermediate EntityRecordContract relation does not have the
     * requested sub-key, getByPath returns null (not an error).
     */
    public function test_get_by_path_returns_null_when_intermediate_entity_record_lacks_segment(): void
    {
        $def = $this->stubDefinition();

        $publicationRecord = new EntityRecord($def, ['id' => 5, 'title' => 'Book']);
        $host = new EntityRecord(
            $def,
            attributes: ['id' => 1],
            relations: ['publication' => $publicationRecord],
        );

        self::assertNull(
            $host->getByPath('publication.nonexistent_relation.title'),
            'getByPath must return null when intermediate segment is not found.',
        );
    }
}
