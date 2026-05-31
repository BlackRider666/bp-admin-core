<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Dashboard;

use BlackParadise\CoreAdmin\Application\UseCases\Dashboard\BuildDashboardViewUseCase;
use LogicException;
use PHPUnit\Framework\TestCase;

final class BuildDashboardViewUseCaseTest extends TestCase
{
    public function test_execute_throws_logic_exception(): void
    {
        $useCase = new BuildDashboardViewUseCase();

        $this->expectException(LogicException::class);

        $useCase->execute();
    }

    public function test_execute_exception_message_describes_intent(): void
    {
        $useCase = new BuildDashboardViewUseCase();

        try {
            $useCase->execute();
            self::fail('Expected LogicException was not thrown.');
        } catch (LogicException $e) {
            self::assertStringContainsString('BuildDashboardViewUseCase', $e->getMessage());
        }
    }
}
