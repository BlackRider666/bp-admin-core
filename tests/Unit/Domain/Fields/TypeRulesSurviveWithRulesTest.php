<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\DateField;
use BlackParadise\CoreAdmin\Domain\Fields\EnumField;
use BlackParadise\CoreAdmin\Domain\Fields\NumberField;
use PHPUnit\Framework\TestCase;

final class TypeRulesSurviveWithRulesTest extends TestCase
{
    public function test_date_keeps_date_after_withrules(): void
    {
        $r = DateField::make('d')->withRules(['required'])->ruleSet()->toArray();
        self::assertContains('required', $r);
        self::assertContains('date', $r);
    }

    public function test_number_keeps_bounds_after_withrules(): void
    {
        $r = NumberField::make('n')->integer()->min(1)->max(5)->withRules(['required'])->ruleSet()->toArray();
        self::assertContains('integer', $r);
        self::assertContains('max:5', $r);
    }

    public function test_enum_keeps_in_after_withrules(): void
    {
        $r = EnumField::make('s', ['a' => 'A'])->withRules(['required'])->ruleSet()->toArray();
        self::assertContains('in:a', $r);
    }

    public function test_belongs_to_keeps_marker_after_withrules(): void
    {
        $r = BelongsToField::make('g_id', 'App\\Models\\Genre')->withRules(['required'])->ruleSet()->toArray();
        self::assertContains('relation_exists:App\\Models\\Genre', $r);
    }
}
