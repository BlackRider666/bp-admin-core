<?php

declare(strict_types=1);

/*
 * Pest bootstrap for bp-admin-core.
 *
 * Існуючі PHPUnit-тести в tests/Unit/ продовжують працювати — Pest 3 запускає
 * їх через свій PHPUnit-сумісний layer без модифікацій. Нові архітектурні
 * тести в tests/Architecture/ пишемо у Pest syntax.
 */

use PHPUnit\Framework\TestCase;

uses(TestCase::class)->in(__DIR__ . '/Unit');
