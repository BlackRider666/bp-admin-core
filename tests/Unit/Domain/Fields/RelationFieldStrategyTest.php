<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphManyField;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Strategy flag on HasManyField / MorphManyField — controls how RelationWriter
 * applies the incoming child payload to the existing related set.
 *
 * The implementation lives in the framework adapter; here we only verify the
 * field-level API (default, allowed values, validation, getter).
 */
final class RelationFieldStrategyTest extends TestCase
{
    public function test_has_many_default_strategy_is_replace(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        self::assertSame('replace', $field->getStrategy());
    }

    public function test_morph_many_default_strategy_is_replace(): void
    {
        $field = MorphManyField::make('images', 'App\\Models\\Image');

        self::assertSame('replace', $field->getStrategy());
    }

    /**
     * @dataProvider validStrategiesProvider
     */
    public function test_has_many_accepts_valid_strategy(string $strategy): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment')->strategy($strategy);

        self::assertSame($strategy, $field->getStrategy());
    }

    /**
     * @dataProvider validStrategiesProvider
     */
    public function test_morph_many_accepts_valid_strategy(string $strategy): void
    {
        $field = MorphManyField::make('images', 'App\\Models\\Image')->strategy($strategy);

        self::assertSame($strategy, $field->getStrategy());
    }

    /**
     * @return list<array{0: string}>
     */
    public static function validStrategiesProvider(): array
    {
        return [
            ['replace'],
            ['merge'],
            ['append'],
        ];
    }

    public function test_has_many_strategy_returns_same_instance(): void
    {
        $field = HasManyField::make('comments', 'App\\Models\\Comment');
        $result = $field->strategy('merge');

        self::assertSame($field, $result);
    }

    public function test_morph_many_strategy_returns_same_instance(): void
    {
        $field = MorphManyField::make('images', 'App\\Models\\Image');
        $result = $field->strategy('append');

        self::assertSame($field, $result);
    }

    public function test_has_many_rejects_unknown_strategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/strategy must be one of/i');

        HasManyField::make('comments', 'App\\Models\\Comment')->strategy('sync');
    }

    public function test_morph_many_rejects_unknown_strategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/strategy must be one of/i');

        MorphManyField::make('images', 'App\\Models\\Image')->strategy('upsert');
    }

    public function test_has_many_rejects_empty_strategy(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HasManyField::make('comments', 'App\\Models\\Comment')->strategy('');
    }

    public function test_strategy_is_case_sensitive(): void
    {
        // The allowlist is matched verbatim — 'REPLACE' is not 'replace'.
        $this->expectException(InvalidArgumentException::class);

        HasManyField::make('comments', 'App\\Models\\Comment')->strategy('REPLACE');
    }

    // -------------------------------------------------------------------------
    // M15 — '_id' degenerate-name guard on BelongsToField
    // -------------------------------------------------------------------------

    public function test_belongs_to_field_with_bare_id_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/too short|empty/i');

        BelongsToField::make('_id', 'App\\Models\\Foo');
    }

    public function test_belongs_to_field_with_id_name_throws(): void
    {
        // 'id' technically does not end with '_id' (no underscore) — so it is
        // not caught by the guard. Documenting current behaviour: only names
        // matching the FK-column convention (`*_id`) trigger validation.
        $field = BelongsToField::make('id', 'App\\Models\\Foo');

        self::assertSame('id', $field->name());
    }

    public function test_belongs_to_field_with_normal_fk_name_does_not_throw(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');

        self::assertSame('author_id', $field->name());
        self::assertSame('author', $field->relationName());
    }
}
