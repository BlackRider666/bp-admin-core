<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Exceptions;

use BlackParadise\CoreAdmin\Domain\Exceptions\UnauthorizedException;
use DomainException;
use PHPUnit\Framework\TestCase;

final class UnauthorizedExceptionTest extends TestCase
{
    public function test_extends_domain_exception(): void
    {
        $exception = new UnauthorizedException('users', 'list');

        self::assertInstanceOf(DomainException::class, $exception);
    }

    public function test_message_contains_action_and_entity(): void
    {
        $exception = new UnauthorizedException('users', 'list');

        self::assertSame("You can't list users", $exception->getMessage());
    }

    public function test_message_with_different_action_and_entity(): void
    {
        $exception = new UnauthorizedException('posts', 'delete');

        self::assertSame("You can't delete posts", $exception->getMessage());
    }

    public function test_code_is_403(): void
    {
        $exception = new UnauthorizedException('users', 'create');

        self::assertSame(403, $exception->getCode());
    }

    public function test_message_with_create_action(): void
    {
        $exception = new UnauthorizedException('orders', 'create');

        self::assertSame("You can't create orders", $exception->getMessage());
    }
}
