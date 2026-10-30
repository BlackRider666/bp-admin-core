<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Action;

use BlackParadise\CoreAdmin\Domain\Action\ActionContext;
use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/** A35 — ActionContext VO: definition, scope, keys, input. */
final class ActionContextTest extends TestCase
{
    public function test_exposes_definition_scope_keys_and_input(): void
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $keys = [new EntityKey(1), new EntityKey(2)];

        $ctx = new ActionContext($definition, ActionScope::Bulk, $keys, ['reason' => 'x']);

        self::assertSame($definition, $ctx->definition);
        self::assertSame(ActionScope::Bulk, $ctx->scope);
        self::assertSame($keys, $ctx->keys);
        self::assertSame(['reason' => 'x'], $ctx->input);
    }

    public function test_keys_and_input_default_to_empty(): void
    {
        $ctx = new ActionContext($this->createMock(EntityDefinitionContract::class), ActionScope::Entity);

        self::assertSame([], $ctx->keys);
        self::assertSame([], $ctx->input);
    }

    public function test_non_entity_key_elements_throw_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ActionContext($this->createMock(EntityDefinitionContract::class), ActionScope::Bulk, [new EntityKey(1), 2]); // @phpstan-ignore argument.type, new.resultUnused
    }

    public function test_scalar_keys_throw_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ActionContext($this->createMock(EntityDefinitionContract::class), ActionScope::Bulk, ['abc']); // @phpstan-ignore argument.type, new.resultUnused
    }

    public function test_entity_key_items_round_trip_in_order(): void
    {
        $a = new EntityKey(5);
        $b = new EntityKey('x-9');

        $ctx = new ActionContext($this->createMock(EntityDefinitionContract::class), ActionScope::Bulk, [$a, $b]);

        self::assertSame([$a, $b], $ctx->keys);
    }

    public function test_class_is_final_readonly(): void
    {
        $ref = new ReflectionClass(ActionContext::class);

        self::assertTrue($ref->isFinal());
        self::assertTrue($ref->isReadOnly());
    }
}
