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
     * @param string $context Validation context: 'create' (default) or 'update'.
     *                        In 'update' context, fields absent from $presentKeys
     *                        have their 'required' rule relaxed to 'sometimes'.
     */
    public function __construct(array $locales = [], private string $context = 'create')
    {
        $this->locales = array_values(
            array_filter($locales, fn(mixed $l): bool => is_string($l) && $l !== ''),
        );
    }

    /**
     * Build validation rules for all fields in the definition.
     *
     * @param list<string> $presentKeys Keys present in the incoming payload.
     *                                  Only relevant in 'update' context — absent fields
     *                                  have their 'required' rule relaxed to 'sometimes'.
     *                                  In 'create' context this parameter is ignored.
     * @return array<string, array<string>>
     */
    public function build(EntityDefinitionContract $definition, array $presentKeys = []): array
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
                    $localeKey   = $field->name() . '.' . $locale;
                    $localeRules = $fieldRules;
                    if ($this->context === 'update' && !in_array($field->name(), $presentKeys, true)) {
                        $localeRules = $this->relaxRequired($localeRules);
                    }
                    $rules[$localeKey] = $localeRules;
                }
            } else {
                $fieldRules = $field->ruleSet()->toArray();
                if ($fieldRules === []) {
                    continue;
                }
                if ($this->context === 'update' && !in_array($field->name(), $presentKeys, true)) {
                    $fieldRules = $this->relaxRequired($fieldRules);
                }
                $rules[$field->name()] = $fieldRules;
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

    /**
     * In 'update' context, replace 'required' with 'sometimes' for absent keys.
     * If 'required' is not present the rules are returned unchanged.
     *
     * @param array<string> $fieldRules
     * @return array<string>
     */
    private function relaxRequired(array $fieldRules): array
    {
        if (!in_array('required', $fieldRules, true)) {
            return $fieldRules;
        }

        $relaxed = array_values(array_filter($fieldRules, fn(string $r): bool => $r !== 'required'));
        array_unshift($relaxed, 'sometimes');
        return $relaxed;
    }
}
