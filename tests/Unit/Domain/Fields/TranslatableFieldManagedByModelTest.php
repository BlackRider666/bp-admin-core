<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use PHPUnit\Framework\TestCase;

final class TranslatableFieldManagedByModelTest extends TestCase
{
    public function test_is_managed_by_model_defaults_to_false(): void
    {
        $field = TranslatableField::make('title');

        self::assertFalse($field->isManagedByModel());
    }

    public function test_managed_by_model_sets_flag_to_true(): void
    {
        $field = TranslatableField::make('title')->managedByModel();

        self::assertTrue($field->isManagedByModel());
    }

    public function test_managed_by_model_returns_same_instance(): void
    {
        $field  = TranslatableField::make('title');
        $result = $field->managedByModel();

        self::assertSame($field, $result);
    }

    public function test_flag_coexists_with_as_editor(): void
    {
        $field = TranslatableField::make('content')
            ->asEditor()
            ->managedByModel();

        self::assertTrue($field->isManagedByModel());
        self::assertSame('editor', $field->innerType());
    }
}
