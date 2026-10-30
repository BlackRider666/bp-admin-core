<?php

declare(strict_types=1);

/**
 * [Slice0] EntityRecord invariants via REAL concrete field objects (A2 behaviour).
 *
 * Ці тести є PRE-GREEN — EntityRecord реалізує правильну поведінку через
 * предикати поля ($field->isSideEffect(), $field->isRelation()). Тести залишаються
 * зеленими як regression guard після будь-яких змін у внутрішньому механізмі диспетчеризації.
 *
 * Критично відрізняються від EntityRecordInvariantsTest тим, що використовують
 * РЕАЛЬНІ concrete field objects, а не createMock(FieldContract::class) з type()-рядком.
 * Це робить тести незалежними від конкретного механізму диспетчеризації в EntityRecord.
 *
 * Mapping A-id ↔ тест:
 *   A2 (EntityRecord invariants preserved) → всі тести цього файлу
 *
 * Всі тести оголошені --pre-green у bp-red.sh — вони зелені ще до реалізації.
 */

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasOneField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphFileField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphManyField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphToField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;

/**
 * Helper: creates a minimal EntityDefinitionContract with the given fields.
 * Name must be unique per file to avoid duplicate-function errors across test suite.
 *
 * @param array<int, FieldContract> $fields
 */
function slice0_invariants_make_definition(array $fields): EntityDefinitionContract
{
    return new class ($fields) implements EntityDefinitionContract {
        /** @param array<int, FieldContract> $fields */
        public function __construct(private readonly array $fields) {}
        public function name(): string
        {
            return 'test_invariants';
        }
        public function label(): string
        {
            return 'Test';
        }
        public function keyField(): string
        {
            return 'id';
        }
        public function keyType(): string
        {
            return 'int';
        }
        public function modelClass(): string
        {
            return 'App\\Models\\TestModel';
        }
        public function fields(): array
        {
            return $this->fields;
        }
        public function actions(): array
        {
            return [];
        }
        public function defaultPerPage(): int
        {
            return 15;
        }
        public function searchFields(): array
        {
            return [];
        }

        public function resolveName(): string
        {
            return 'test_invariants';
        }

        public function capabilities(): EntityCapabilities
        {
            return EntityCapabilities::all();
        }

        /**
         * @return array<mixed>
         */
        public function filters(): array
        {
            return [];
        }

        public function repository(): null
        {
            return null;
        }

        public function mutator(): null
        {
            return null;
        }
    };
}

// =========================================================================
// setField() on side-effect relation fields → MUST throw DomainException
// (pre-green: isSideEffect() predicate returns true for these concrete types)
// =========================================================================

it('[Slice0] setField on BelongsToManyField throws DomainException', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    expect(fn() => $record->setField(new FieldName('roles'), [1, 2, 3]))
        ->toThrow(DomainException::class);
});

it('[Slice0] setField on HasManyField throws DomainException', function (): void {
    $field = HasManyField::make('posts', 'App\\Models\\Post');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    expect(fn() => $record->setField(new FieldName('posts'), [['title' => 'A']]))
        ->toThrow(DomainException::class);
});

it('[Slice0] setField on HasOneField throws DomainException', function (): void {
    $field = HasOneField::make('profile', 'App\\Models\\Profile');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    expect(fn() => $record->setField(new FieldName('profile'), ['bio' => 'hello']))
        ->toThrow(DomainException::class);
});

it('[Slice0] setField on MorphManyField throws DomainException', function (): void {
    $field = MorphManyField::make('comments', 'App\\Models\\Comment');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    expect(fn() => $record->setField(new FieldName('comments'), []))
        ->toThrow(DomainException::class);
});

it('[Slice0] setField on MorphFileField throws DomainException', function (): void {
    $field = MorphFileField::make('avatar');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    expect(fn() => $record->setField(new FieldName('avatar'), 'file-data'))
        ->toThrow(DomainException::class);
});

// =========================================================================
// setField() on dual-nature relation fields (host-FK) → MUST NOT throw
// (pre-green: isSideEffect()=false for BelongsTo and MorphTo — host stores the FK)
// =========================================================================

it('[Slice0] setField on BelongsToField does not throw (host owns FK column)', function (): void {
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    $record->setField(new FieldName('author_id'), 42);

    expect($record->getField(new FieldName('author_id')))->toBe(42);
});

it('[Slice0] setField on MorphToField does not throw (host owns type+id FK columns)', function (): void {
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    $record->setField(new FieldName('taggable'), 'App\\Models\\Post:7');

    expect($record->getField(new FieldName('taggable')))->toBe('App\\Models\\Post:7');
});

// =========================================================================
// setRelation() on scalar field → MUST throw DomainException
// (pre-green: isRelation()=false for TextField — scalar field, not a relation)
// =========================================================================

it('[Slice0] setRelation on TextField throws DomainException', function (): void {
    $field = TextField::make('title');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    expect(fn() => $record->setRelation(new FieldName('title'), 'some value'))
        ->toThrow(DomainException::class);
});

// =========================================================================
// setRelation() on known relation fields → MUST NOT throw
// (pre-green: isRelation()=true for all concrete relation field types)
// =========================================================================

it('[Slice0] setRelation on BelongsToManyField does not throw', function (): void {
    $field = BelongsToManyField::make('roles', 'App\\Models\\Role');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    $record->setRelation(new FieldName('roles'), [1, 2, 3]);

    expect($record->relation('roles'))->toBe([1, 2, 3]);
});

it('[Slice0] setRelation on BelongsToField does not throw (stores in relations)', function (): void {
    // BelongsToField.isRelation()=true → setRelation allowed.
    $field = BelongsToField::make('author_id', 'App\\Models\\Author');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    $record->setRelation(new FieldName('author_id'), ['id' => 7, 'name' => 'Alice']);

    expect($record->relation('author_id'))->toBe(['id' => 7, 'name' => 'Alice']);
    expect($record->getField(new FieldName('author_id')))->toBeNull();
});

it('[Slice0] setRelation on MorphToField does not throw (stores in relations)', function (): void {
    // MorphToField.isRelation()=true → setRelation allowed.
    $field = MorphToField::make('taggable', 'App\\Models\\Tag');
    $def = slice0_invariants_make_definition([$field]);
    $record = new EntityRecord($def);

    $record->setRelation(new FieldName('taggable'), ['id' => 42, 'type' => 'App\\Models\\Post']);

    expect($record->relation('taggable'))->toBe(['id' => 42, 'type' => 'App\\Models\\Post']);
    expect($record->getField(new FieldName('taggable')))->toBeNull();
});
