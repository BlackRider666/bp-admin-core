<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use BlackParadise\CoreAdmin\Application\UseCases\Action\ExecuteActionUseCase;
use BlackParadise\CoreAdmin\Domain\Action\ActionContext;
use BlackParadise\CoreAdmin\Domain\Action\ActionScope;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\ActionResolverContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\InvalidDefinitionException;
use PHPUnit\Framework\TestCase;
use Tests\Doubles\InMemoryValidationProvider;
use Tests\Doubles\InMemoryWriteScope;
use Tests\Doubles\RecordingEventDispatcher;

/** Review 6.2 finding 3a: findAction surfaces an unresolvable action class naming definition AND class. */
final class ExecuteActionUnresolvableClassTest extends TestCase
{
    private const BROKEN = 'App\\BPAdmin\\Actions\\GhostAction';

    public function test_execute_when_resolver_rejects_class_throws_invalid_definition_naming_definition_and_class(): void
    {
        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('name')->willReturn('ghost_entity');
        $definition->method('actions')->willReturn([self::BROKEN]);
        $resolver = $this->createMock(ActionResolverContract::class);
        $resolver->method('resolve')->willThrowException(
            new InvalidDefinitionException('Cannot resolve action ' . self::BROKEN),
        );
        $events = new RecordingEventDispatcher();
        $useCase = new ExecuteActionUseCase(
            $resolver,
            $this->createMock(AuthorizationProviderContract::class),
            new InMemoryValidationProvider(),
            $this->createMock(LocaleProviderContract::class),
            (new InMemoryWriteScope())->attach($events),
            $events,
        );

        try {
            $useCase->execute($definition, 'anything', new ActionContext($definition, ActionScope::Entity, [], []));
            self::fail('Expected InvalidDefinitionException');
        } catch (InvalidDefinitionException $e) {
            self::assertStringContainsString('ghost_entity', $e->getMessage());
            self::assertStringContainsString(self::BROKEN, $e->getMessage());
        }
    }
}
