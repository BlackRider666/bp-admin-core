<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use PHPUnit\Framework\TestCase;

final class BelongsToFieldRulesTest extends TestCase
{
    public function test_emits_relation_exists_marker_and_nullable(): void
    {
        $rules = BelongsToField::make('genre_id', 'App\\Models\\Genre')->ruleSet()->toArray();
        self::assertContains('nullable', $rules);
        self::assertContains('relation_exists:App\\Models\\Genre', $rules);
    }

    public function test_required_suppresses_nullable(): void
    {
        $rules = BelongsToField::make('genre_id', 'App\\Models\\Genre')->required()->ruleSet()->toArray();
        self::assertContains('required', $rules);
        self::assertNotContains('nullable', $rules);
        self::assertContains('relation_exists:App\\Models\\Genre', $rules);
    }
}
