<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use PHPUnit\Framework\TestCase;

/**
 * F-1: `->searchable()` must be observable through a public getter (not only folded into filterable).
 */
final class FieldSearchableFlagTest extends TestCase
{
    private function isSearchable(object $field): mixed
    {
        self::assertTrue(method_exists($field, 'isSearchable'), 'AbstractField::isSearchable() must exist');

        return $field->isSearchable();
    }

    public function test_is_searchable_when_not_marked_returns_false(): void
    {
        self::assertFalse($this->isSearchable(TextField::make('name')));
    }

    public function test_is_searchable_after_searchable_call_returns_true(): void
    {
        self::assertTrue($this->isSearchable(TextField::make('name')->searchable()));
    }

    public function test_is_searchable_when_only_filterable_returns_false(): void
    {
        self::assertFalse($this->isSearchable(TextField::make('name')->filterable()));
    }
}
