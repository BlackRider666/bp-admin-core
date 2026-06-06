<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Auth\BuildLoginFormViewUseCase;
use BlackParadise\CoreAdmin\Application\UseCases\Dashboard\BuildDashboardViewUseCase;
use PHPUnit\Framework\TestCase;

/**
 * A8 (B19): Guard test — dead-stub use cases must NOT exist.
 *
 * BuildLoginFormViewUseCase and BuildDashboardViewUseCase are dead stubs that
 * unconditionally throw LogicException. They must be removed from the codebase.
 *
 * Currently BUGS: both classes exist → these tests are RED.
 */
final class DeadStubUseCasesRemovedTest extends TestCase
{
    public function test_build_login_form_view_use_case_does_not_exist(): void
    {
        self::assertFalse(
            class_exists(BuildLoginFormViewUseCase::class, autoload: true),
            'BuildLoginFormViewUseCase must be removed — it is a dead stub that always throws LogicException.',
        );
    }

    public function test_build_dashboard_view_use_case_does_not_exist(): void
    {
        self::assertFalse(
            class_exists(BuildDashboardViewUseCase::class, autoload: true),
            'BuildDashboardViewUseCase must be removed — it is a dead stub that always throws LogicException.',
        );
    }
}
