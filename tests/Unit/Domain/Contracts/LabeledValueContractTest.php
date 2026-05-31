<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Contracts;

use BlackParadise\CoreAdmin\Domain\Contracts\LabeledValueContract;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LabeledValueContractTest extends TestCase
{
    public function test_concrete_value_object_can_implement_contract(): void
    {
        $vo = new class (1) implements LabeledValueContract {
            public function __construct(public readonly int $value) {}
            public function label(): string
            {
                return 'Active';
            }
            public static function options(): array
            {
                return [1 => 'Active', 2 => 'Banned'];
            }
        };

        self::assertSame('Active', $vo->label());
        self::assertSame([1 => 'Active', 2 => 'Banned'], $vo::options());
    }

    public function test_contract_is_an_interface(): void
    {
        $reflection = new ReflectionClass(LabeledValueContract::class);

        self::assertTrue($reflection->isInterface());
    }
}
