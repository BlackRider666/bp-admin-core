<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Validation;

use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;

interface ValidationProviderContract
{
    /**
     * Validate $data against $rules.
     *
     * Rules are field-name-keyed arrays of rule strings, matching the
     * structure returned by {@see FieldContract::rules()}.
     *
     * Example:
     *   ['name' => ['required', 'max:255'], 'email' => ['required', 'email']]
     *
     * @param array<string, mixed> $data
     * @param array<string, array<string>> $rules
     *
     * @throws ValidationException when validation fails
     */
    public function validate(array $data, array $rules): void;
}
