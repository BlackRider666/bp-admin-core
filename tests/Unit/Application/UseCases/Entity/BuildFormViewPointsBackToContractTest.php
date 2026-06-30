<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\BuildFormViewUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\StubEmbeddedDefinitionWithCustomBackRef;

/**
 * Slice-2 follow-up (N1): BuildFormViewUseCase must honour the
 * FieldContract::pointsBackTo() predicate for the embedded FK-skip (A7), not an
 * inlined `instanceof RelationFieldContract && kind()===BelongsTo && target()===host`
 * check. The inlined form misses any field that declares pointsBackTo()=true via a
 * different mechanism than a BelongsTo target — which violates the field contract.
 */
final class BuildFormViewPointsBackToContractTest extends TestCase
{
    public function test_embedded_fk_skip_honours_pointsBackTo_predicate_not_inlined_belongsTo_check(): void
    {
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->method('can')->willReturn(true);

        $container = HasManyField::make('items', 'App\\Models\\Item')
            ->embed(StubEmbeddedDefinitionWithCustomBackRef::class);

        $definition = $this->createMock(EntityDefinitionContract::class);
        $definition->method('fields')->willReturn([$container]);
        $definition->method('modelClass')->willReturn('App\\Models\\Host');

        $useCase = new BuildFormViewUseCase($auth, $definition);
        $result  = $useCase->execute('create');

        $embeddedFields = $result[0]->meta()['embeddedFields'] ?? [];
        $names          = array_map(
            static fn(FieldContract $f): string => $f->name(),
            $embeddedFields,
        );

        self::assertContains('keep', $names);
        self::assertNotContains(
            'custom_back_ref',
            $names,
            'a sub-field declaring pointsBackTo()=true must be FK-skipped from embeddedFields even when it is not a BelongsToField',
        );
    }
}
