<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts;

interface LocaleProviderContract
{
    /**
     * @return string[] List of available locales, e.g. ['en', 'uk', 'de']
     */
    public function availableLocales(): array;

    /**
     * The default (primary) locale.
     */
    public function defaultLocale(): string;
}
