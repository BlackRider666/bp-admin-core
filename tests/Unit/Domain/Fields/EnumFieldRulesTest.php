<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\EnumField;
use PHPUnit\Framework\TestCase;

final class EnumFieldRulesTest extends TestCase
{
    public function test_single_select_emits_in_rule_with_option_keys(): void
    {
        $rules = EnumField::make('status', ['draft' => 'Draft', 'published' => 'Published'])->ruleSet()->toArray();
        self::assertContains('in:draft,published', $rules);
    }

    public function test_multiple_still_emits_array(): void
    {
        $rules = EnumField::make('tags', ['a' => 'A', 'b' => 'B'])->multiple()->ruleSet()->toArray();
        self::assertContains('array', $rules);
    }
}
