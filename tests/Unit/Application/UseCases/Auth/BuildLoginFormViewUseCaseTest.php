<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Auth;

use BlackParadise\CoreAdmin\Application\UseCases\Auth\BuildLoginFormViewUseCase;
use LogicException;
use PHPUnit\Framework\TestCase;

final class BuildLoginFormViewUseCaseTest extends TestCase
{
    public function test_execute_throws_logic_exception(): void
    {
        $useCase = new BuildLoginFormViewUseCase();

        $this->expectException(LogicException::class);

        $useCase->execute();
    }

    public function test_execute_exception_message_describes_intent(): void
    {
        $useCase = new BuildLoginFormViewUseCase();

        try {
            $useCase->execute();
            self::fail('Expected LogicException was not thrown.');
        } catch (LogicException $e) {
            self::assertStringContainsString('BuildLoginFormViewUseCase', $e->getMessage());
        }
    }
}
