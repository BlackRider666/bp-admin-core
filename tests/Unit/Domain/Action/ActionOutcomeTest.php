<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Action;

use BlackParadise\CoreAdmin\Domain\Action\ActionOutcome;
use PHPUnit\Framework\TestCase;

/** A38 — toast level of an action result. */
final class ActionOutcomeTest extends TestCase
{
    public function test_outcome_enum_has_success_info_warning_error_cases(): void
    {
        self::assertTrue(enum_exists(ActionOutcome::class), 'ActionOutcome enum must exist.');
        self::assertSame(
            ['Success', 'Info', 'Warning', 'Error'],
            array_map(static fn(ActionOutcome $o): string => $o->name, ActionOutcome::cases()),
        );
    }

    public function test_outcome_values_are_lowercase_strings(): void
    {
        self::assertSame('success', ActionOutcome::Success->value);
        self::assertSame('info', ActionOutcome::Info->value);
        self::assertSame('warning', ActionOutcome::Warning->value);
        self::assertSame('error', ActionOutcome::Error->value);
    }
}
