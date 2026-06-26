<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RelationFieldDisplayLabelTest extends TestCase
{
    public function test_falls_back_to_display_field_when_no_callback(): void
    {
        $field = BelongsToField::make('journal_issue_id', stdClass::class)
            ->withDisplayField('number');

        self::assertFalse($field->hasDisplayCallback());
        self::assertSame('9', $field->resolveDisplayLabel(['number' => 9], 'number'));
        self::assertSame('', $field->resolveDisplayLabel(['number' => ['x' => 1]], 'number'));
    }

    public function test_uses_closure_over_row_array(): void
    {
        $field = BelongsToField::make('journal_issue_id', stdClass::class)
            ->withDisplayField('number')
            ->withDisplayEagerLoad(['history'])
            ->withDisplayUsing(static function (array $row): string {
                $rawHistory = $row['history'] ?? null;
                $history    = is_array($rawHistory) ? $rawHistory : [];
                $rawTitle   = $history['title'] ?? null;
                $title      = is_array($rawTitle) ? $rawTitle : [];
                $rawEn      = $title['en'] ?? null;
                $en         = is_string($rawEn) ? $rawEn : '?';
                $rawNumber  = $row['number'] ?? null;
                $number     = is_string($rawNumber) || is_int($rawNumber) ? (string) $rawNumber : '';

                return $en . ' — №' . $number;
            });

        self::assertTrue($field->hasDisplayCallback());
        self::assertSame(['history'], $field->displayEagerLoad());
        self::assertSame(
            'World Studies — №9',
            $field->resolveDisplayLabel(
                ['number' => 9, 'history' => ['title' => ['en' => 'World Studies']]],
                'number',
            ),
        );
    }

    public function test_display_order_column_defaults_null(): void
    {
        $field = BelongsToField::make('journal_issue_id', stdClass::class);
        self::assertNull($field->displayOrderColumn());
        self::assertSame('issues.number', $field->withDisplayOrderColumn('issues.number')->displayOrderColumn());
    }
}
