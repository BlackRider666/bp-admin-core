<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;
use DomainException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class EntityRecordInvariantsTest extends TestCase
{
    private EntityDefinitionContract $definition;

    protected function setUp(): void
    {
        $emailField = $this->createMock(FieldContract::class);
        $emailField->method('name')->willReturn('email');
        $emailField->method('type')->willReturn('text');

        $rolesField = $this->createMock(FieldContract::class);
        $rolesField->method('name')->willReturn('roles');
        $rolesField->method('type')->willReturn('belongs_to_many');

        $authorField = $this->createMock(FieldContract::class);
        $authorField->method('name')->willReturn('author');
        $authorField->method('type')->willReturn('belongs_to');

        $morphableField = $this->createMock(FieldContract::class);
        $morphableField->method('name')->willReturn('morphable');
        $morphableField->method('type')->willReturn('morph_to');

        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('keyField')->willReturn('id');
        $this->definition->method('name')->willReturn('users');
        $this->definition->method('fields')->willReturn([$emailField, $rolesField, $authorField, $morphableField]);
    }

    public function test_set_field_stores_value_for_column_field(): void
    {
        $record = new EntityRecord($this->definition);

        $record->setField(new FieldName('email'), 'a@b.com');

        self::assertSame('a@b.com', $record->getField(new FieldName('email')));
        self::assertNull($record->relation('email'));
    }

    public function test_set_field_throws_when_field_not_in_definition(): void
    {
        $record = new EntityRecord($this->definition);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/unknown field "missing"/i');

        $record->setField(new FieldName('missing'), 'x');
    }

    public function test_set_field_throws_when_field_is_side_effect_relation(): void
    {
        $record = new EntityRecord($this->definition);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/relation .*roles/i');

        $record->setField(new FieldName('roles'), [1, 2, 3]);
    }

    public function test_set_field_allows_belongs_to_column(): void
    {
        $record = new EntityRecord($this->definition);

        $record->setField(new FieldName('author'), 7);

        self::assertSame(7, $record->getField(new FieldName('author')));
    }

    public function test_set_relation_stores_value_for_relation_field(): void
    {
        $record = new EntityRecord($this->definition);

        $record->setRelation(new FieldName('roles'), [1, 2, 3]);

        self::assertSame([1, 2, 3], $record->relation('roles'));
        self::assertNull($record->get('roles'));
    }

    public function test_set_relation_allows_belongs_to(): void
    {
        $record = new EntityRecord($this->definition);

        $record->setRelation(new FieldName('author'), 'user-7');

        self::assertSame('user-7', $record->relation('author'));
        self::assertNull($record->getField(new FieldName('author')));
    }

    public function test_set_field_allows_morph_to_column(): void
    {
        $record = new EntityRecord($this->definition);

        $record->setField(new FieldName('morphable'), 'App\Models\Post:7');

        self::assertSame('App\Models\Post:7', $record->getField(new FieldName('morphable')));
        self::assertNull($record->relation('morphable'));
    }

    public function test_set_relation_allows_morph_to(): void
    {
        $record = new EntityRecord($this->definition);
        $polymorph = new stdClass();

        $record->setRelation(new FieldName('morphable'), $polymorph);

        self::assertSame($polymorph, $record->relation('morphable'));
        self::assertNull($record->getField(new FieldName('morphable')));
    }

    public function test_set_relation_throws_when_field_not_in_definition(): void
    {
        $record = new EntityRecord($this->definition);

        $this->expectException(DomainException::class);

        $record->setRelation(new FieldName('missing'), [1]);
    }

    public function test_set_relation_throws_when_field_is_pure_column(): void
    {
        $record = new EntityRecord($this->definition);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not a relation/i');

        $record->setRelation(new FieldName('email'), [1]);
    }

    public function test_get_field_returns_null_when_not_set(): void
    {
        $record = new EntityRecord($this->definition);

        self::assertNull($record->getField(new FieldName('email')));
    }

    public function test_has_field_returns_true_when_present(): void
    {
        $record = new EntityRecord($this->definition, ['email' => 'a@b.com']);

        self::assertTrue($record->hasField(new FieldName('email')));
    }

    public function test_has_field_returns_false_when_absent(): void
    {
        $record = new EntityRecord($this->definition);

        self::assertFalse($record->hasField(new FieldName('email')));
    }
}
