<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\DateField;
use PHPUnit\Framework\TestCase;

final class DateFieldTest extends TestCase
{
    public function test_emits_date_rule(): void
    {
        self::assertContains('date', DateField::make('published_on')->ruleSet()->toArray());
    }

    public function test_required_date_emits_required_and_date(): void
    {
        $rules = DateField::make('published_on')->required()->ruleSet()->toArray();
        self::assertContains('required', $rules);
        self::assertContains('date', $rules);
    }

    public function test_optional_date_emits_nullable(): void
    {
        $rules = DateField::make('published_on')->ruleSet()->toArray();
        self::assertContains('nullable', $rules);
        self::assertContains('date', $rules);
    }

    public function test_required_date_suppresses_nullable(): void
    {
        $rules = DateField::make('published_on')->required()->ruleSet()->toArray();
        self::assertContains('required', $rules);
        self::assertNotContains('nullable', $rules);
    }
}
