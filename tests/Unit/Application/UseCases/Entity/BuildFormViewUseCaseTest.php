<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\BuildFormViewUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\RelationOptionsProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\RelationFieldContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToManyField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\StubEmbeddedDefinition;

final class BuildFormViewUseCaseTest extends TestCase
{
    private AuthorizationProviderContract $auth;
    private EntityDefinitionContract $definition;

    protected function setUp(): void
    {
        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->definition = $this->createMock(EntityDefinitionContract::class);
        $this->definition->method('name')->willReturn('users');
    }

    public function test_execute_when_authorized_returns_only_form_visible_fields(): void
    {
        $visibleField = TextField::make('name');
        $hiddenField = TextField::make('internal_notes')->hideFromForm();

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$visibleField, $hiddenField]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertCount(1, $result);
        self::assertSame($visibleField, $result[0]);
    }

    public function test_execute_when_authorized_with_all_visible_fields_returns_all(): void
    {
        $field1 = TextField::make('name');
        $field2 = TextField::make('email');
        $field3 = TextField::make('bio');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field1, $field2, $field3]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertCount(3, $result);
    }

    public function test_execute_when_authorized_with_no_visible_fields_returns_empty_array(): void
    {
        $field1 = TextField::make('audit_log')->hideFromForm();
        $field2 = TextField::make('system_flag')->hideFromForm();

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field1, $field2]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertSame([], $result);
    }

    public function test_execute_when_unauthorized_throws_unauthorized_exception(): void
    {
        $this->auth->method('can')->willReturn(false);
        $this->definition->method('fields')->willReturn([]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("You can't create users");

        $useCase->execute('create');
    }

    public function test_execute_uses_provided_action_for_authorization(): void
    {
        $this->auth
            ->expects(self::once())
            ->method('can')
            ->with('edit', $this->definition)
            ->willReturn(false);

        $this->definition->method('fields')->willReturn([]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);

        try {
            $useCase->execute('edit');
        } catch (UnauthorizedException) {
            // expected
        }
    }

    public function test_execute_result_has_consecutive_integer_keys(): void
    {
        $field1 = TextField::make('a');
        $field2 = TextField::make('b')->hideFromForm();
        $field3 = TextField::make('c');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field1, $field2, $field3]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertSame([0, 1], array_keys($result));
    }

    // -------------------------------------------------------------------------
    // RelationOptionsProviderContract integration
    // -------------------------------------------------------------------------

    public function test_execute_without_options_provider_does_not_call_anything(): void
    {
        // Optional provider — three-arg constructor without provider must work
        // (covers unit-test usage paths and adapters that don't need options).
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition);
        $result = $useCase->execute('create');

        self::assertCount(1, $result);
        self::assertSame([], $result[0]->meta());
    }

    public function test_execute_with_options_provider_decorates_belongs_to_field_meta(): void
    {
        $field = BelongsToField::make('author_id', 'App\\Models\\Author');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider
            ->expects(self::once())
            ->method('options')
            ->with(self::isInstanceOf(RelationFieldContract::class))
            ->willReturn([
                ['id' => 1, 'label' => 'Alice'],
                ['id' => 2, 'label' => 'Bob'],
            ]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $result = $useCase->execute('create');

        self::assertSame(
            [
                'options' => [
                    ['id' => 1, 'label' => 'Alice'],
                    ['id' => 2, 'label' => 'Bob'],
                ],
            ],
            $result[0]->meta(),
        );
    }

    public function test_execute_with_options_provider_decorates_belongs_to_many_field(): void
    {
        $field = BelongsToManyField::make('roles', 'App\\Models\\Role');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider
            ->expects(self::once())
            ->method('options')
            ->willReturn([['id' => 10, 'label' => 'Editor']]);

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $result = $useCase->execute('create');

        self::assertSame(
            [['id' => 10, 'label' => 'Editor']],
            $result[0]->meta()['options'] ?? null,
        );
    }

    public function test_execute_with_options_provider_skips_embedded_relation(): void
    {
        // Embedded relations render their nested fieldset via a different
        // presenter path; the options list would be meaningless and the
        // provider call would be wasted I/O.
        $field = BelongsToField::make('author_id', 'App\\Models\\Author')
            ->embed(StubEmbeddedDefinition::class);

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->expects(self::never())->method('options');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $useCase->execute('create');
    }

    public function test_execute_with_options_provider_skips_has_many(): void
    {
        // has_many is a write-side relation rendered as a nested fieldset/list,
        // not a select. Provider must not be queried.
        $field = HasManyField::make('comments', 'App\\Models\\Comment');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->expects(self::never())->method('options');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $useCase->execute('create');
    }

    public function test_execute_with_options_provider_skips_non_relation_fields(): void
    {
        $field = TextField::make('title');

        $provider = $this->createMock(RelationOptionsProviderContract::class);
        $provider->expects(self::never())->method('options');

        $this->auth->method('can')->willReturn(true);
        $this->definition->method('fields')->willReturn([$field]);

        $useCase = new BuildFormViewUseCase($this->auth, $this->definition, $provider);
        $useCase->execute('create');
    }
}
