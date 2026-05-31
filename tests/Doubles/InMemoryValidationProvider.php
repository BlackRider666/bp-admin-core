<?php

declare(strict_types=1);

namespace Tests\Doubles;

use BlackParadise\CoreAdmin\Domain\Contracts\Validation\ValidationProviderContract;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;

/**
 * Pass-through validator for tests.
 *
 * Default — accept everything (validate() is a no-op).
 *
 * Configure failure mode via failNext():
 *
 *     $validator = new InMemoryValidationProvider();
 *     $validator->failNext(['email' => ['email is required']]);
 *     $useCase->execute(...); // throws ValidationException once, then resets.
 *
 * Inputs to validate() are recorded for assertions:
 *
 *     $this->assertSame(['name' => 'John'], $validator->lastData);
 *     $this->assertSame(['name' => ['required']], $validator->lastRules);
 */
final class InMemoryValidationProvider implements ValidationProviderContract
{
    /** @var array<string, list<string>>|null */
    private ?array $nextErrors = null;

    public int $validateCalls = 0;

    /** @var array<string, mixed>|null */
    public ?array $lastData = null;

    /** @var array<string, array<string>>|null */
    public ?array $lastRules = null;

    /**
     * Queue a validation failure for the next validate() call.
     *
     * @param array<string, list<string>> $errors
     */
    public function failNext(array $errors): self
    {
        $this->nextErrors = $errors;
        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array<string>> $rules
     */
    public function validate(array $data, array $rules): void
    {
        ++$this->validateCalls;
        $this->lastData  = $data;
        $this->lastRules = $rules;

        if ($this->nextErrors !== null) {
            $errs = $this->nextErrors;
            $this->nextErrors = null;
            throw new ValidationException($errs);
        }
    }
}
