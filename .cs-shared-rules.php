<?php

declare(strict_types=1);

/*
 * Single source of truth for BPAdmin v3 PHP code-style rules.
 *
 * Consumed by:
 *   - code/v3/bp-admin-core/.php-cs-fixer.dist.php (PHP-CS-Fixer)
 *   - code/v3/scripts/sync-pint.php (generates code/v3/pint.json for Laravel Pint)
 *
 * Базовий ruleset (PER-CS 2.0) додається в кожному tool-конфігу окремо
 * (PHP-CS-Fixer: '@PER-CS2.0' => true; Pint: "preset": "per"). Цей файл
 * містить ТІЛЬКИ кастомні правила поверх PER-CS 2.0 (див. wiki standards/coding-style.md §"Custom Rules").
 */

return [
    'declare_strict_types' => true,
    'global_namespace_import' => [
        'import_classes' => true,
        'import_constants' => false,
        'import_functions' => false,
    ],
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
    'no_unused_imports' => true,
    'single_quote' => true,
    'concat_space' => ['spacing' => 'one'],
    'phpdoc_align' => ['align' => 'left'],
    'void_return' => true,
    'self_static_accessor' => true,
];
