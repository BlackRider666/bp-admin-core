<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\TranslatableField;

/**
 * Builds a field-keyed validation rules array from an EntityDefinition.
 *
 * Translatable fields require special handling: their data arrives as a
 * locale-keyed map (`{ "en": "...", "uk": "..." }`) so a plain `required`
 * rule on the top-level key always passes (non-empty array). The rules must
 * be expanded to per-locale dot-notation keys instead:
 *
 *   title.en => ['required', ...]
 *   title.uk => ['required', ...]
 *
 * The list of locales is supplied by the caller (injected at construction time
 * via the domain's {@see \BlackParadise\CoreAdmin\Domain\Contracts\LocaleProviderContract}
 * in the Laravel adapter). When no locales are provided the translatable field
 * falls back to a single "required" check on the field name itself, matching
 * legacy behaviour.
 */
final readonly class RuleBuilder
{
    /** @var list<string> */
    private array $locales;

    /**
     * @param array<mixed> $locales Ordered list of locale codes (e.g. ['en', 'uk']).
     *                              Supplied by the adapter from LocaleProviderContract.
     *                              When empty, translatable fields are NOT expanded.
     *                              Non-string values and empty strings are silently
     *                              dropped to prevent malformed keys like "title.";
     *                              the constructor sanitises the raw adapter input.
     */
    public function __construct(array $locales = [])
    {
        $this->locales = array_values(
            array_filter($locales, fn(mixed $l): bool => is_string($l) && $l !== ''),
        );
    }

    /**
     * Build validation rules for all fields in the definition.
     *
     * @return array<string, array<string>>
     */
    public function build(EntityDefinitionContract $definition): array
    {
        $rules = [];
        foreach ($definition->fields() as $field) {
            if ($field instanceof TranslatableField && $this->locales !== []) {
                $fieldRules = $field->ruleSet()->toArray();
                if ($fieldRules === []) {
                    continue;
                }
                // Expand to per-locale dot-notation keys.
                foreach ($this->locales as $locale) {
                    $rules[$field->name() . '.' . $locale] = $fieldRules;
                }
            } else {
                $fieldRules = $field->ruleSet()->toArray();
                if ($fieldRules !== []) {
                    $rules[$field->name()] = $fieldRules;
                }
            }
        }
        return $rules;
    }

    /**
     * Convenience static factory that uses no locale expansion (legacy path).
     *
     * @return array<string, array<string>>
     */
    public static function fromDefinition(EntityDefinitionContract $definition): array
    {
        return (new self())->build($definition);
    }
}
