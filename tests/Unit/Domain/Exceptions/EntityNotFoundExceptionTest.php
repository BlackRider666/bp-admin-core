<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Exceptions;

use BlackParadise\CoreAdmin\Domain\Exceptions\EntityNotFoundException;
use DomainException;
use PHPUnit\Framework\TestCase;

final class EntityNotFoundExceptionTest extends TestCase
{
    public function test_domain_namespaced_entity_not_found_exception_constructs(): void
    {
        $exception = new EntityNotFoundException('Book', 7);

        self::assertInstanceOf(DomainException::class, $exception);
        self::assertSame('Entity [Book] with key [7] not found.', $exception->getMessage());
    }
}
