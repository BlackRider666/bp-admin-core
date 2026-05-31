<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Validation;

enum Rule: string
{
    case Required = 'required';
    case Nullable = 'nullable';
    case String = 'string';
    case Integer = 'integer';
    case Numeric = 'numeric';
    case Email = 'email';
    case Boolean = 'boolean';
    case Date = 'date';
    case Array = 'array';
    case File = 'file';
    case Image = 'image';
    case Url = 'url';
}
