<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Contracts\LabeledValueContract;
use BlackParadise\CoreAdmin\Domain\Fields\EnumField;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

enum EnumFieldSourceTestStatus: string
{
    case Active = 'active';
    case Banned = 'banned';
}

enum EnumFieldSourceTestPriority: int implements LabeledValueContract
{
    case Low  = 1;
    case High = 2;

    public function label(): string
    {
        return match ($this) {
            self::Low  => 'Низький',
            self::High => 'Високий',
        };
    }

    public static function options(): array
    {
        return [
            self::Low->value  => 'Низький',
            self::High->value => 'Високий',
        ];
    }
}

final readonly class EnumFieldSourceTestVO implements LabeledValueContract
{
    public function __construct(public int $value) {}

    public function label(): string
    {
        return self::options()[$this->value] ?? 'Unknown';
    }

    public static function options(): array
    {
        return [1 => 'One', 2 => 'Two', 3 => 'Three'];
    }
}

final class EnumFieldSourceTest extends TestCase
{
    public function test_legacy_array_options_still_work(): void
    {
        $field = EnumField::make('status', ['a' => 'A', 'b' => 'B']);

        self::assertSame(['a' => 'A', 'b' => 'B'], $field->options());
    }

    public function test_backed_enum_string_class_generates_options_from_cases(): void
    {
        $field = EnumField::make('status', EnumFieldSourceTestStatus::class);

        self::assertSame(
            ['active' => 'Active', 'banned' => 'Banned'],
            $field->options(),
        );
    }

    public function test_backed_enum_implementing_labeled_value_uses_label_method(): void
    {
        $field = EnumField::make('priority', EnumFieldSourceTestPriority::class);

        self::assertSame(
            [1 => 'Низький', 2 => 'Високий'],
            $field->options(),
        );
    }

    public function test_plain_labeled_value_vo_calls_static_options(): void
    {
        $field = EnumField::make('kind', EnumFieldSourceTestVO::class);

        self::assertSame(
            [1 => 'One', 2 => 'Two', 3 => 'Three'],
            $field->options(),
        );
    }

    public function test_unknown_class_throws_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/NotAClass|not a valid options source/i');

        EnumField::make('x', 'NotAClass');
    }

    public function test_non_enum_non_contract_class_throws(): void
    {
        $class = new class {
            public function hello(): string
            {
                return 'hi';
            }
        };

        $this->expectException(InvalidArgumentException::class);

        EnumField::make('x', $class::class);
    }

    public function test_meta_exposes_resolved_options(): void
    {
        $field = EnumField::make('status', EnumFieldSourceTestStatus::class);

        self::assertSame(
            ['active' => 'Active', 'banned' => 'Banned'],
            $field->meta()['options'] ?? null,
        );
    }
}
