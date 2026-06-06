<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\NumberField;
use PHPUnit\Framework\TestCase;

final class NumberFieldTest extends TestCase
{
    public function test_min_max_emit_rules(): void
    {
        $rules = NumberField::make('rating')->integer()->min(1)->max(5)->ruleSet()->toArray();
        self::assertContains('integer', $rules);
        self::assertContains('min:1', $rules);
        self::assertContains('max:5', $rules);
    }

    public function test_default_numeric_without_bounds(): void
    {
        $rules = NumberField::make('price')->ruleSet()->toArray();
        self::assertContains('numeric', $rules);
        self::assertNotContains('min:0', $rules);
    }

    public function test_optional_number_emits_nullable(): void
    {
        $rules = NumberField::make('price')->ruleSet()->toArray();
        self::assertContains('nullable', $rules);
        self::assertContains('numeric', $rules);
    }

    public function test_required_number_suppresses_nullable(): void
    {
        $rules = NumberField::make('price')->required()->ruleSet()->toArray();
        self::assertContains('required', $rules);
        self::assertNotContains('nullable', $rules);
    }
}
