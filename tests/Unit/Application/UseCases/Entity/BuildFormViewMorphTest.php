<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\BuildFormViewUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\RelationOptionsProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\RelationFieldContract;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphToField;
use PHPUnit\Framework\TestCase;

/**
 * Task C.2 — morphOptions() contract + BuildFormView morphTo decoration.
 *
 * Verifies that BuildFormViewUseCase calls morphOptions() on the injected
 * RelationOptionsProviderContract for morphTo fields and attaches the result
 * to the field's meta under the key 'morphOptions'.
 */
final class BuildFormViewMorphTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper: authz provider that always allows
    // -------------------------------------------------------------------------

    private function allowAll(): AuthorizationProviderContract
    {
        $auth = $this->createMock(AuthorizationProviderContract::class);
        $auth->method('can')->willReturn(true);

        return $auth;
    }

    // -------------------------------------------------------------------------
    // Helper: entity definition with a single morphTo field
    // -------------------------------------------------------------------------

    private function definitionWith(MorphToField $field): EntityDefinitionContract
    {
        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('name')->willReturn('comments');
        $def->method('fields')->willReturn([$field]);

        return $def;
    }

    // -------------------------------------------------------------------------
    // Helper: RelationOptionsProviderContract fake
    // -------------------------------------------------------------------------
    /**
     * Returns a provider whose morphOptions() returns a fixed per-type list.
     */
    private function fakeProvider(): RelationOptionsProviderContract
    {
        $provider = $this->createMock(RelationOptionsProviderContract::class);

        $provider
            ->method('options')
            ->willReturn([]);

        $provider
            ->method('morphOptions')
            ->willReturn([
                [
                    'value'   => 'book',
                    'label'   => 'Book',
                    'options' => [
                        ['id' => 1, 'label' => 'Dune'],
                    ],
                ],
            ]);

        return $provider;
    }

    // =========================================================================
    // Core assertion — morphOptions added to field meta
    // =========================================================================

    public function test_morph_to_field_receives_morph_options_in_meta(): void
    {
        $morphField = MorphToField::make('commentable', 'App\\Models\\Book')
            ->morphTypes(['App\\Models\\Book' => 'Book']);

        $useCase = new BuildFormViewUseCase(
            $this->allowAll(),
            $this->definitionWith($morphField),
            $this->fakeProvider(),
        );

        $fields = $useCase->execute('create');

        self::assertCount(1, $fields);
        $morph = $fields[0];

        self::assertArrayHasKey('morphOptions', $morph->meta());
        self::assertSame('book', $morph->meta()['morphOptions'][0]['value']);
    }

    // =========================================================================
    // morphOptions() on provider is called exactly once for morphTo field
    // =========================================================================

    public function test_morph_options_provider_is_called_once_for_morph_to(): void
    {
        $morphField = MorphToField::make('commentable', 'App\\Models\\Book')
            ->morphTypes(['App\\Models\\Book' => 'Book']);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([]);
        $provider
            ->expects(self::once())
            ->method('morphOptions')
            ->with(self::isInstanceOf(RelationFieldContract::class))
            ->willReturn([
                [
                    'value'   => 'book',
                    'label'   => 'Book',
                    'options' => [['id' => 1, 'label' => 'Dune']],
                ],
            ]);

        $useCase = new BuildFormViewUseCase(
            $this->allowAll(),
            $this->definitionWith($morphField),
            $provider,
        );

        $useCase->execute('create');
    }

    // =========================================================================
    // morphOptions() is NOT called for non-morphTo relation fields
    // =========================================================================

    public function test_morph_options_not_called_for_belongs_to_field(): void
    {
        // BelongsToField should use options(), not morphOptions()
        $belongsTo = BelongsToField::make(
            'author_id',
            'App\\Models\\Author',
        );

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([['id' => 1, 'label' => 'Alice']]);
        $provider->expects(self::never())->method('morphOptions');

        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('name')->willReturn('posts');
        $def->method('fields')->willReturn([$belongsTo]);

        $useCase = new BuildFormViewUseCase($this->allowAll(), $def, $provider);
        $useCase->execute('create');
    }

    // =========================================================================
    // morphOptions result shape preserved verbatim in meta
    // =========================================================================

    public function test_morph_options_meta_matches_provider_output_exactly(): void
    {
        $morphField = MorphToField::make('commentable', 'App\\Models\\Book')
            ->morphTypes(['App\\Models\\Book' => 'Book', 'App\\Models\\Article' => 'Article']);

        $expectedOptions = [
            [
                'value'   => 'book',
                'label'   => 'Book',
                'options' => [
                    ['id' => 1, 'label' => 'Dune'],
                    ['id' => 2, 'label' => 'Foundation'],
                ],
            ],
            [
                'value'   => 'article',
                'label'   => 'Article',
                'options' => [
                    ['id' => 10, 'label' => 'PHPWorld 2025'],
                ],
            ],
        ];

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->method('options')->willReturn([]);
        $provider->method('morphOptions')->willReturn($expectedOptions);

        $useCase = new BuildFormViewUseCase(
            $this->allowAll(),
            $this->definitionWith($morphField),
            $provider,
        );

        $fields = $useCase->execute('create');
        $meta   = $fields[0]->meta();

        self::assertArrayHasKey('morphOptions', $meta);
        self::assertSame($expectedOptions, $meta['morphOptions']);
    }

    // =========================================================================
    // Without provider — no morphOptions key set
    // =========================================================================

    public function test_without_provider_morph_to_field_has_no_morph_options_meta(): void
    {
        $morphField = MorphToField::make('commentable', 'App\\Models\\Book')
            ->morphTypes(['App\\Models\\Book' => 'Book']);

        $useCase = new BuildFormViewUseCase(
            $this->allowAll(),
            $this->definitionWith($morphField),
            // No provider
        );

        $fields = $useCase->execute('create');

        self::assertCount(1, $fields);
        self::assertArrayNotHasKey('morphOptions', $fields[0]->meta());
    }
}
