<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Events;

use BlackParadise\CoreAdmin\Domain\Action\ActionOutcome;
use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Contracts\Events\DomainEventContract;
use BlackParadise\CoreAdmin\Domain\Events\ActionExecuted;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;

final class ActionExecutedTest extends TestCase
{
    /** @param list<EntityKey> $keys */
    private function event(array $keys, ActionScope $scope = ActionScope::Bulk): ActionExecuted
    {
        return new ActionExecuted(
            'posts',
            'archive',
            $scope,
            $keys,
            ActionResult::message('done', ActionOutcome::Warning),
        );
    }

    public function test_it_is_a_domain_event(): void
    {
        self::assertInstanceOf(DomainEventContract::class, $this->event([]));
    }

    public function test_properties_are_exposed(): void
    {
        $keys = [new EntityKey(1)];
        $result = ActionResult::message('done');

        $event = new ActionExecuted('posts', 'archive', ActionScope::Row, $keys, $result);

        self::assertSame('posts', $event->entity);
        self::assertSame('archive', $event->action);
        self::assertSame(ActionScope::Row, $event->scope);
        self::assertSame($keys, $event->keys);
        self::assertSame($result, $event->result);
    }

    public function test_get_entity_key_returns_first_key(): void
    {
        $first = new EntityKey(5);

        $event = $this->event([$first, new EntityKey(6)]);

        self::assertSame($first, $event->getEntityKey());
    }

    public function test_get_entity_key_without_keys_returns_entity_scope_sentinel(): void
    {
        $event = $this->event([], ActionScope::Entity);

        $key = $event->getEntityKey();

        self::assertSame(ActionExecuted::ENTITY_SCOPE_KEY, $key->value);
        self::assertSame('*', $key->value);
        self::assertSame('string', $key->type);
    }

    public function test_get_payload_describes_the_execution(): void
    {
        $event = $this->event([new EntityKey(1), new EntityKey('abc', 'string')]);

        self::assertSame(
            [
                'entity' => 'posts',
                'action' => 'archive',
                'scope' => 'bulk',
                'keys' => [1, 'abc'],
                'outcome' => 'warning',
            ],
            $event->getPayload(),
        );
    }
}
