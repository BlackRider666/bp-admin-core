<?php

declare(strict_types=1);

/*
 * PHP-CS-Fixer config for bp-admin-core.
 *
 * Custom rules live in .cs-shared-rules.php (shared with Pint).
 * This file adds the PER-CS 2.0 base ruleset and points the Finder at src/ + tests/.
 */

$sharedRules = require __DIR__ . '/.cs-shared-rules.php';

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules(array_merge(['@PER-CS2.0' => true], $sharedRules))
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
