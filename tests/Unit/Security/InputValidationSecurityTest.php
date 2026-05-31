<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\Query\Criteria;
use BlackParadise\CoreAdmin\Domain\Query\Filter;
use BlackParadise\CoreAdmin\Domain\Query\PaginatedResult;
use BlackParadise\CoreAdmin\Domain\Query\Sort;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use BlackParadise\CoreAdmin\Domain\ValueObjects\FieldName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Security-focused tests for input validation and boundary conditions
 * across core domain objects.
 */
final class InputValidationSecurityTest extends TestCase
{
    // -------------------------------------------------------------------------
    // EntityKey — type safety
    // -------------------------------------------------------------------------

    public function test_entity_key_rejects_invalid_type_value(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EntityKey(1, 'object');
    }

    public function test_entity_key_rejects_sql_injection_in_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EntityKey(1, "int'; DROP TABLE users; --");
    }

    public function test_entity_key_with_very_long_string_value(): void
    {
        $longKey = str_repeat('a', 10000);
        $key = new EntityKey($longKey, 'string');

        self::assertSame($longKey, $key->value);
    }

    public function test_entity_key_with_special_characters(): void
    {
        $key = new EntityKey("O'Brien", 'string');
        self::assertSame("O'Brien", $key->value);

        $key2 = new EntityKey('key"with"quotes', 'string');
        self::assertSame('key"with"quotes', $key2->value);
    }

    public function test_entity_key_with_unicode_value(): void
    {
        $key = new EntityKey('ключ-😀', 'string');
        self::assertSame('ключ-😀', $key->value);
    }

    // -------------------------------------------------------------------------
    // Filter — malicious input patterns
    // -------------------------------------------------------------------------

    public function test_filter_stores_sql_injection_attempt_as_literal_value(): void
    {
        $filter = new Filter('name', "'; DROP TABLE users; --");

        self::assertSame("'; DROP TABLE users; --", $filter->value);
        self::assertSame('name', $filter->field);
    }

    public function test_filter_accepts_field_with_sql_injection_attempt(): void
    {
        // Filter itself is a value object — validation happens at repository level
        $filter = new Filter('name; DROP TABLE users', 'value');

        self::assertSame('name; DROP TABLE users', $filter->field);
    }

    public function test_filter_with_html_script_tag_in_value(): void
    {
        $filter = new Filter('name', '<script>alert("xss")</script>');

        self::assertSame('<script>alert("xss")</script>', $filter->value);
    }

    // -------------------------------------------------------------------------
    // Sort — malicious direction values
    // -------------------------------------------------------------------------

    public function test_sort_stores_values_without_validation(): void
    {
        // Sort is a value object — validation at repository layer
        $sort = new Sort('name', 'asc');

        self::assertSame('name', $sort->field);
        self::assertSame('asc', $sort->direction);
    }

    public function test_sort_rejects_sql_injection_in_direction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sort direction must be 'asc' or 'desc'");

        new Sort('name', 'asc; DROP TABLE users');
    }

    public function test_sort_rejects_arbitrary_direction(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Sort('name', 'DROP');
    }

    public function test_sort_with_sql_injection_in_field(): void
    {
        $sort = new Sort('name; DROP TABLE users', 'asc');

        self::assertSame('name; DROP TABLE users', $sort->field);
    }

    // -------------------------------------------------------------------------
    // Criteria — boundary values
    // -------------------------------------------------------------------------

    public function test_criteria_with_negative_page(): void
    {
        $criteria = new Criteria(page: -1);

        self::assertSame(-1, $criteria->page);
        // Repository is responsible for max(1, page)
    }

    public function test_criteria_with_zero_page(): void
    {
        $criteria = new Criteria(page: 0);

        self::assertSame(0, $criteria->page);
    }

    public function test_criteria_with_negative_per_page(): void
    {
        $criteria = new Criteria(perPage: -10);

        self::assertSame(-10, $criteria->perPage);
    }

    public function test_criteria_with_zero_per_page(): void
    {
        $criteria = new Criteria(perPage: 0);

        self::assertSame(0, $criteria->perPage);
    }

    public function test_criteria_with_extremely_large_per_page(): void
    {
        $criteria = new Criteria(perPage: PHP_INT_MAX);

        self::assertSame(PHP_INT_MAX, $criteria->perPage);
    }

    public function test_criteria_with_sql_injection_in_search(): void
    {
        $criteria = new Criteria(search: "'; DROP TABLE users; --");

        self::assertSame("'; DROP TABLE users; --", $criteria->search);
    }

    public function test_criteria_with_xss_in_search(): void
    {
        $criteria = new Criteria(search: '<img src=x onerror=alert(1)>');

        self::assertSame('<img src=x onerror=alert(1)>', $criteria->search);
    }

    public function test_criteria_with_very_long_search_string(): void
    {
        $longSearch = str_repeat('a', 100000);
        $criteria = new Criteria(search: $longSearch);

        self::assertSame($longSearch, $criteria->search);
    }

    public function test_criteria_with_null_bytes_in_search(): void
    {
        $criteria = new Criteria(search: "test\0injection");

        self::assertSame("test\0injection", $criteria->search);
    }

    // -------------------------------------------------------------------------
    // PaginatedResult — boundary conditions
    // -------------------------------------------------------------------------

    public function test_paginated_result_with_zero_total(): void
    {
        $result = new PaginatedResult(items: [], total: 0, page: 1, perPage: 10);

        self::assertSame(1, $result->lastPage());
        self::assertFalse($result->hasPages());
    }

    public function test_paginated_result_with_zero_per_page(): void
    {
        $result = new PaginatedResult(items: [], total: 100, page: 1, perPage: 0);

        self::assertSame(1, $result->lastPage());
    }

    public function test_paginated_result_with_negative_per_page(): void
    {
        $result = new PaginatedResult(items: [], total: 100, page: 1, perPage: -1);

        self::assertSame(1, $result->lastPage());
    }

    public function test_paginated_result_with_negative_total(): void
    {
        $result = new PaginatedResult(items: [], total: -1, page: 1, perPage: 10);

        // Negative total should not cause crash; lastPage handles gracefully
        self::assertIsInt($result->lastPage());
    }

    public function test_paginated_result_last_page_calculation(): void
    {
        $result = new PaginatedResult(items: [], total: 51, page: 1, perPage: 10);

        self::assertSame(6, $result->lastPage());
        self::assertTrue($result->hasPages());
    }

    public function test_paginated_result_exact_page_boundary(): void
    {
        $result = new PaginatedResult(items: [], total: 50, page: 1, perPage: 10);

        self::assertSame(5, $result->lastPage());
    }

    // -------------------------------------------------------------------------
    // EntityRecord — mass assignment / attribute isolation
    // -------------------------------------------------------------------------

    public function test_entity_record_only_returns_set_attributes(): void
    {
        $definition = $this->createStub(EntityDefinitionContract::class);
        $definition->method('keyField')->willReturn('id');

        $record = new EntityRecord($definition, ['name' => 'Alice', 'email' => 'alice@test.com']);

        self::assertSame(['name' => 'Alice', 'email' => 'alice@test.com'], $record->attributes());
        self::assertNull($record->get('password'));
    }

    public function test_entity_record_to_array_returns_only_attributes(): void
    {
        $definition = $this->createStub(EntityDefinitionContract::class);
        $definition->method('keyField')->willReturn('id');

        $record = new EntityRecord(
            $definition,
            ['name' => 'Alice'],
            ['roles' => ['admin', 'user']],
        );

        // toArray should return ONLY attributes, not relations
        self::assertSame(['name' => 'Alice'], $record->toArray());
        self::assertArrayNotHasKey('roles', $record->toArray());
    }

    public function test_entity_record_stores_xss_payload_as_raw_value(): void
    {
        $definition = $this->createStub(EntityDefinitionContract::class);
        $definition->method('keyField')->willReturn('id');

        $xss = '<script>alert("xss")</script>';
        $record = new EntityRecord($definition, ['name' => $xss]);

        // EntityRecord stores raw values — escaping is the presenter's job
        self::assertSame($xss, $record->get('name'));
    }

    public function test_entity_record_stores_sql_injection_as_raw_value(): void
    {
        $definition = $this->createStub(EntityDefinitionContract::class);
        $definition->method('keyField')->willReturn('id');

        $sql = "'; DROP TABLE users; --";
        $record = new EntityRecord($definition, ['name' => $sql]);

        self::assertSame($sql, $record->get('name'));
    }

    // -------------------------------------------------------------------------
    // FieldName — validation
    // -------------------------------------------------------------------------

    public function test_field_name_with_sql_injection_attempt(): void
    {
        // FieldName is a value object; malicious names should still be storable
        // but validation at allowlist level (repo) prevents them from reaching SQL
        $fieldName = new FieldName('name; DROP TABLE users');

        self::assertSame('name; DROP TABLE users', $fieldName->value);
    }

    // -------------------------------------------------------------------------
    // AbstractField — label XSS
    // -------------------------------------------------------------------------

    public function test_field_label_with_html_returns_raw_html(): void
    {
        $field = TextField::make('name')
            ->withLabel('<script>alert("xss")</script>');

        // Label stores raw value — escaping is the Blade/presenter's responsibility
        self::assertSame('<script>alert("xss")</script>', $field->label());
    }

    public function test_field_auto_label_from_name_with_special_chars(): void
    {
        $field = TextField::make('user_<script>');

        self::assertSame('User <script>', $field->label());
    }
}
