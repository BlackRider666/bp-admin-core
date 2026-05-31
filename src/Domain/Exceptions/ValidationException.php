<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Exceptions;

use DomainException;

final class ValidationException extends DomainException
{
    /**
     * @param array<string, array<string>> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed.',
    ) {
        parent::__construct($message);
    }

    /** @return array<string, array<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
