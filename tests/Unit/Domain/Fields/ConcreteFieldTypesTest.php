<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\BooleanField;
use BlackParadise\CoreAdmin\Domain\Fields\DateField;
use BlackParadise\CoreAdmin\Domain\Fields\DateTimeField;
use BlackParadise\CoreAdmin\Domain\Fields\EditorField;
use BlackParadise\CoreAdmin\Domain\Fields\FileField;
use BlackParadise\CoreAdmin\Domain\Fields\HashedField;
use BlackParadise\CoreAdmin\Domain\Fields\ImageField;
use BlackParadise\CoreAdmin\Domain\Fields\MorphFileField;
use BlackParadise\CoreAdmin\Domain\Fields\NumberField;
use BlackParadise\CoreAdmin\Domain\Fields\PhoneField;
use BlackParadise\CoreAdmin\Domain\Fields\TextareaField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;
use PHPUnit\Framework\TestCase;

final class ConcreteFieldTypesTest extends TestCase
{
    public function test_text_field_type_returns_text(): void
    {
        self::assertSame('text', TextField::make('name')->type());
    }

    public function test_text_field_make_creates_instance(): void
    {
        $field = TextField::make('title');

        self::assertInstanceOf(TextField::class, $field);
        self::assertSame('title', $field->name());
    }

    public function test_number_field_type_returns_number(): void
    {
        self::assertSame('number', NumberField::make('age')->type());
    }

    public function test_number_field_make_creates_instance(): void
    {
        $field = NumberField::make('price');

        self::assertInstanceOf(NumberField::class, $field);
        self::assertSame('price', $field->name());
    }

    public function test_boolean_field_type_returns_boolean(): void
    {
        self::assertSame('boolean', BooleanField::make('is_active')->type());
    }

    public function test_boolean_field_make_creates_instance(): void
    {
        $field = BooleanField::make('is_active');

        self::assertInstanceOf(BooleanField::class, $field);
        self::assertSame('is_active', $field->name());
    }

    public function test_date_time_field_type_returns_datetime(): void
    {
        self::assertSame('datetime', DateTimeField::make('created_at')->type());
    }

    public function test_date_time_field_make_creates_instance(): void
    {
        $field = DateTimeField::make('published_at');

        self::assertInstanceOf(DateTimeField::class, $field);
        self::assertSame('published_at', $field->name());
    }

    public function test_file_field_type_returns_file(): void
    {
        self::assertSame('file', FileField::make('document')->type());
    }

    public function test_file_field_make_creates_instance(): void
    {
        $field = FileField::make('attachment');

        self::assertInstanceOf(FileField::class, $field);
        self::assertSame('attachment', $field->name());
    }

    public function test_image_field_type_returns_image(): void
    {
        self::assertSame('image', ImageField::make('avatar')->type());
    }

    public function test_image_field_make_creates_instance(): void
    {
        $field = ImageField::make('thumbnail');

        self::assertInstanceOf(ImageField::class, $field);
        self::assertSame('thumbnail', $field->name());
    }

    public function test_morph_file_field_type_returns_morph_file(): void
    {
        self::assertSame('morph_file', MorphFileField::make('media')->type());
    }

    public function test_morph_file_field_make_creates_instance(): void
    {
        $field = MorphFileField::make('files');

        self::assertInstanceOf(MorphFileField::class, $field);
        self::assertSame('files', $field->name());
    }

    public function test_editor_field_type_returns_editor(): void
    {
        self::assertSame('editor', EditorField::make('content')->type());
    }

    public function test_editor_field_make_creates_instance(): void
    {
        $field = EditorField::make('body');

        self::assertInstanceOf(EditorField::class, $field);
        self::assertSame('body', $field->name());
    }

    public function test_hashed_field_type_returns_hashed(): void
    {
        self::assertSame('hashed', HashedField::make('password')->type());
    }

    public function test_hashed_field_make_creates_instance(): void
    {
        $field = HashedField::make('password_hash');

        self::assertInstanceOf(HashedField::class, $field);
        self::assertSame('password_hash', $field->name());
    }

    public function test_textarea_field_type_returns_textarea(): void
    {
        self::assertSame('textarea', TextareaField::make('description')->type());
    }

    public function test_textarea_field_make_creates_instance(): void
    {
        $field = TextareaField::make('notes');

        self::assertInstanceOf(TextareaField::class, $field);
        self::assertSame('notes', $field->name());
    }

    public function test_date_field_type_returns_date(): void
    {
        self::assertSame('date', DateField::make('birthday')->type());
    }

    public function test_date_field_make_creates_instance(): void
    {
        $field = DateField::make('published_on');

        self::assertInstanceOf(DateField::class, $field);
        self::assertSame('published_on', $field->name());
    }

    public function test_phone_field_type_returns_phone(): void
    {
        self::assertSame('phone', PhoneField::make('phone')->type());
    }

    public function test_phone_field_make_creates_instance(): void
    {
        $field = PhoneField::make('mobile');

        self::assertInstanceOf(PhoneField::class, $field);
        self::assertSame('mobile', $field->name());
    }

    public function test_translatable_field_type_returns_translatable(): void
    {
        self::assertSame('translatable', TranslatableField::make('title')->type());
    }

    public function test_translatable_field_make_creates_instance(): void
    {
        $field = TranslatableField::make('name');

        self::assertInstanceOf(TranslatableField::class, $field);
        self::assertSame('name', $field->name());
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_all_concrete_fields_have_correct_default_visibility(string $type): void
    {
        $field = $type::make('test_field');

        self::assertTrue($field->visibleOnList());
        self::assertTrue($field->visibleOnForm());
        self::assertTrue($field->visibleOnShow());
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_all_concrete_fields_are_not_sortable_by_default(string $type): void
    {
        $field = $type::make('test_field');

        self::assertFalse($field->isSortable());
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_all_concrete_fields_are_not_filterable_by_default(string $type): void
    {
        $field = $type::make('test_field');

        self::assertFalse($field->isFilterable());
    }

    public static function fieldTypeProvider(): array
    {
        return [
            'TextField' => [TextField::class],
            'NumberField' => [NumberField::class],
            'BooleanField' => [BooleanField::class],
            'DateTimeField' => [DateTimeField::class],
            'FileField' => [FileField::class],
            'ImageField' => [ImageField::class],
            'MorphFileField' => [MorphFileField::class],
            'EditorField' => [EditorField::class],
            'HashedField' => [HashedField::class],
            'TextareaField' => [TextareaField::class],
            'DateField' => [DateField::class],
            'PhoneField' => [PhoneField::class],
            'TranslatableField' => [TranslatableField::class],
        ];
    }

    // -------------------------------------------------------------------------
    // withMeta on each field type
    // -------------------------------------------------------------------------

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_with_meta_stores_metadata_on_each_field_type(string $type): void
    {
        $field = $type::make('test_field')->withMeta(['placeholder' => 'Enter value']);

        self::assertSame(['placeholder' => 'Enter value'], $field->meta());
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_with_meta_merges_multiple_calls_on_each_field_type(string $type): void
    {
        $field = $type::make('test_field')
            ->withMeta(['key_a' => 'value_a'])
            ->withMeta(['key_b' => 'value_b']);

        self::assertSame(['key_a' => 'value_a', 'key_b' => 'value_b'], $field->meta());
    }

    // -------------------------------------------------------------------------
    // Fluent chaining returns same instance for each type
    // -------------------------------------------------------------------------

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_fluent_chaining_returns_same_instance_for_each_type(string $type): void
    {
        $field = $type::make('test_field');

        $result = $field
            ->withLabel('Test Label')
            ->required()
            ->sortable()
            ->filterable()
            ->withMeta(['hint' => 'some hint'])
            ->hideFromList()
            ->hideFromForm()
            ->hideFromShow();

        self::assertSame($field, $result);
        self::assertSame('Test Label', $field->label());
        self::assertContains('required', $field->rules());
        self::assertTrue($field->isSortable());
        self::assertTrue($field->isFilterable());
        self::assertSame(['hint' => 'some hint'], $field->meta());
        self::assertFalse($field->visibleOnList());
        self::assertFalse($field->visibleOnForm());
        self::assertFalse($field->visibleOnShow());
    }

    // -------------------------------------------------------------------------
    // withRules on concrete fields
    // -------------------------------------------------------------------------

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_with_rules_replaces_rules_on_each_field_type(string $type): void
    {
        $field = $type::make('test_field')->withRules(['required', 'max:255']);

        // Verifies the specified rules are present. File-type fields additionally
        // append allowlist constraints (mimes:/mimetypes:) as a separate layer,
        // so we check containment rather than exact equality.
        self::assertContains('required', $field->rules());
        self::assertContains('max:255', $field->rules());
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_with_rules_returns_same_instance_on_each_field_type(string $type): void
    {
        $field = $type::make('test_field');
        $result = $field->withRules(['nullable']);

        self::assertSame($field, $result);
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_required_adds_rule_on_each_field_type(string $type): void
    {
        $field = $type::make('test_field')->required();

        self::assertContains('required', $field->rules());
    }

    /**
     * @dataProvider fieldTypeProvider
     */
    public function test_nullable_adds_rule_on_each_field_type(string $type): void
    {
        $field = $type::make('test_field')->nullable();

        self::assertContains('nullable', $field->rules());
    }
}
