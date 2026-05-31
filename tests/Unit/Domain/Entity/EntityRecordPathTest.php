<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use PHPUnit\Framework\TestCase;

final class EntityRecordPathTest extends TestCase
{
    private function stubDefinition(): EntityDefinitionContract
    {
        $stub = $this->createStub(EntityDefinitionContract::class);
        $stub->method('keyField')->willReturn('id');
        return $stub;
    }

    public function test_get_by_path_returns_plain_attribute_without_dot(): void
    {
        $record = new EntityRecord($this->stubDefinition(), ['title' => 'Hello']);

        self::assertSame('Hello', $record->getByPath('title'));
    }

    public function test_get_by_path_resolves_one_level_relation(): void
    {
        $record = new EntityRecord(
            $this->stubDefinition(),
            attributes: ['id' => 1],
            relations: ['publication' => ['title' => 'Book', 'year' => 2026]],
        );

        self::assertSame('Book', $record->getByPath('publication.title'));
        self::assertSame(2026, $record->getByPath('publication.year'));
    }

    public function test_get_by_path_resolves_nested_relation(): void
    {
        $record = new EntityRecord(
            $this->stubDefinition(),
            relations: [
                'publication' => [
                    'title'  => 'Book',
                    'author' => ['name' => 'Tolkien', 'year' => 1954],
                ],
            ],
        );

        self::assertSame('Tolkien', $record->getByPath('publication.author.name'));
    }

    public function test_get_by_path_returns_null_for_missing_relation(): void
    {
        $record = new EntityRecord($this->stubDefinition(), relations: []);

        self::assertNull($record->getByPath('publication.title'));
    }

    public function test_get_by_path_returns_null_for_missing_attribute_in_loaded_relation(): void
    {
        $record = new EntityRecord(
            $this->stubDefinition(),
            relations: ['publication' => ['title' => 'Book']],
        );

        self::assertNull($record->getByPath('publication.nonexistent'));
    }

    public function test_get_by_path_returns_null_for_null_intermediate(): void
    {
        $record = new EntityRecord(
            $this->stubDefinition(),
            relations: ['publication' => null],
        );

        self::assertNull($record->getByPath('publication.title'));
    }
}
