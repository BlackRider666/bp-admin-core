<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Tests\Unit\Domain\Contracts;

use BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract;
use PHPUnit\Framework\TestCase;

final class LocaleProviderContractTest extends TestCase
{
    /**
     * A conforming implementation must satisfy all three locale methods —
     * availableLocales(), defaultLocale() and the runtime-display currentLocale().
     * The anonymous class would be abstract-incomplete (fatal) if the contract
     * did not declare currentLocale(), so this exercises the contract surface.
     */
    public function test_implementation_provides_all_locale_methods(): void
    {
        $provider = new class implements LocaleProviderContract {
            public function availableLocales(): array
            {
                return ['en', 'de'];
            }

            public function defaultLocale(): string
            {
                return 'en';
            }

            public function currentLocale(): string
            {
                return 'de';
            }
        };

        self::assertSame(['en', 'de'], $provider->availableLocales());
        self::assertSame('en', $provider->defaultLocale());
        self::assertSame('de', $provider->currentLocale());
    }
}
