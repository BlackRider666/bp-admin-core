<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Contracts;

use BlackParadise\CoreAdmin\Domain\Contracts\ValueHasherContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A13 (B11): ValueHasherContract must declare isHashed(string): bool.
 *
 * Currently BUGS: the interface only has hash(string): string.
 * These tests are RED against current code.
 */
final class ValueHasherContractTest extends TestCase
{
    public function test_value_hasher_contract_declares_is_hashed_method(): void
    {
        self::assertTrue(
            method_exists(ValueHasherContract::class, 'isHashed'),
            'ValueHasherContract must declare isHashed(string): bool method.',
        );
    }

    public function test_value_hasher_contract_is_hashed_accepts_string_parameter(): void
    {
        $reflection = new ReflectionMethod(ValueHasherContract::class, 'isHashed');
        $params = $reflection->getParameters();

        self::assertCount(1, $params, 'isHashed() must accept exactly one parameter.');
        self::assertTrue(
            $params[0]->hasType(),
            'isHashed() parameter must be type-hinted.',
        );
        self::assertSame(
            'string',
            (string) $params[0]->getType(),
            'isHashed() parameter must be of type string.',
        );
    }

    public function test_value_hasher_contract_is_hashed_returns_bool(): void
    {
        $reflection = new ReflectionMethod(ValueHasherContract::class, 'isHashed');

        self::assertNotNull(
            $reflection->getReturnType(),
            'isHashed() must declare a return type.',
        );
        self::assertSame(
            'bool',
            (string) $reflection->getReturnType(),
            'isHashed() must return bool.',
        );
    }

    public function test_value_hasher_contract_still_declares_hash_method(): void
    {
        // Verify the existing hash() method was not accidentally removed.
        self::assertTrue(
            method_exists(ValueHasherContract::class, 'hash'),
            'ValueHasherContract must still declare hash(string): string method.',
        );
    }
}
