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
     *
     * Stable, configuration-derived value. Use for deterministic concerns such
     * as sort order, where the result must not shift with the viewer's runtime
     * locale.
     */
    public function defaultLocale(): string;

    /**
     * The locale currently active at runtime (e.g. after the locale-switch
     * middleware called app()->setLocale()). Use for DISPLAY — translatable
     * values, relation labels, default form locale tab. Implementations fall
     * back to {@see defaultLocale()} when no runtime locale is set.
     */
    public function currentLocale(): string;
}
