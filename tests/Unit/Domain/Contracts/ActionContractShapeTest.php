<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Contracts;

use BlackParadise\CoreAdmin\Domain\Action\ActionContext;
use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionContract;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Pins the target shape of ActionContract (spec 08): scopes()/handle()/permission()/fields(), no scope().
 */
final class ActionContractShapeTest extends TestCase
{
    private function method(string $name): ?ReflectionMethod
    {
        $reflection = new ReflectionClass(ActionContract::class);

        return $reflection->hasMethod($name) ? $reflection->getMethod($name) : null;
    }

    public function test_contract_declares_scopes_returning_array(): void
    {
        $type = $this->method('scopes')?->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $type);
        self::assertSame('array', $type->getName());
    }

    public function test_contract_declares_handle_taking_context_and_returning_result(): void
    {
        $method = $this->method('handle');

        self::assertInstanceOf(ReflectionMethod::class, $method);
        $params = $method->getParameters();
        self::assertCount(1, $params);
        $paramType = $params[0]->getType();
        self::assertInstanceOf(ReflectionNamedType::class, $paramType);
        self::assertSame(ActionContext::class, $paramType->getName());
        $returnType = $method->getReturnType();
        self::assertInstanceOf(ReflectionNamedType::class, $returnType);
        self::assertSame(ActionResult::class, $returnType->getName());
    }

    public function test_contract_declares_permission_returning_nullable_string(): void
    {
        $type = $this->method('permission')?->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $type);
        self::assertSame('string', $type->getName());
        self::assertTrue($type->allowsNull());
    }

    public function test_contract_declares_fields_returning_array(): void
    {
        $type = $this->method('fields')?->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $type);
        self::assertSame('array', $type->getName());
    }

    public function test_contract_no_longer_declares_legacy_scope(): void
    {
        self::assertFalse((new ReflectionClass(ActionContract::class))->hasMethod('scope'));
    }
}
