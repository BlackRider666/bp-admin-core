<?php

declare(strict_types=1);

namespace Tests\Doubles;

use ReflectionMethod;
use ReflectionProperty;

/**
 * Reflection-style helpers for RED-first tests that target classes which do not exist yet.
 * Keeps the suite loadable (and static analysis stable) before AND after the implementation lands.
 */
final class Dyn
{
    /**
     * @param array<int|string, mixed> $args positional or named constructor arguments
     */
    public static function make(string $fqcn, array $args = []): object
    {
        $class = $fqcn;

        return new $class(...$args);
    }

    public static function implements(string $fqcn, string $interface): bool
    {
        $implemented = class_exists($fqcn) ? class_implements($fqcn) : [];

        return is_array($implemented) && in_array($interface, $implemented, true);
    }

    public static function call(object $target, string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod($target, $method))->invokeArgs($target, $args);
    }

    public static function prop(object $target, string $property): mixed
    {
        return (new ReflectionProperty($target, $property))->getValue($target);
    }
}
