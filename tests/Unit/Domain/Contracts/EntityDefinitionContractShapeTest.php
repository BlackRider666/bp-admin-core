<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Contracts;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityCapabilities;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Pins the target shape of EntityDefinitionContract (A29, A31; spec 07/U-02).
 * Reflection only: stays loadable before the contract changes.
 */
final class EntityDefinitionContractShapeTest extends TestCase
{
    private function method(string $name): ?ReflectionMethod
    {
        $reflection = new ReflectionClass(EntityDefinitionContract::class);

        return $reflection->hasMethod($name) ? $reflection->getMethod($name) : null;
    }

    private function returnType(string $name): ?string
    {
        $type = $this->method($name)?->getReturnType();

        return $type instanceof ReflectionNamedType
            ? ($type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '') . $type->getName()
            : null;
    }

    public function test_contract_declares_capabilities_returning_entity_capabilities(): void
    {
        self::assertSame(EntityCapabilities::class, $this->returnType('capabilities'));
    }

    public function test_contract_declares_public_instance_resolve_name_returning_string(): void
    {
        $method = $this->method('resolveName');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        self::assertTrue($method->isPublic());
        self::assertFalse($method->isStatic());
        self::assertSame('string', $this->returnType('resolveName'));
    }

    public function test_contract_declares_filters_returning_array(): void
    {
        self::assertSame('array', $this->returnType('filters'));
    }

    public function test_contract_declares_repository_returning_nullable_string(): void
    {
        self::assertSame('?string', $this->returnType('repository'));
    }

    public function test_contract_declares_mutator_returning_nullable_string(): void
    {
        self::assertSame('?string', $this->returnType('mutator'));
    }

    public function test_contract_declares_actions_returning_array(): void
    {
        self::assertSame('array', $this->returnType('actions'));
    }

    public function test_contract_declares_model_class_returning_nullable_string(): void
    {
        self::assertSame('?string', $this->returnType('modelClass'));
    }
}
