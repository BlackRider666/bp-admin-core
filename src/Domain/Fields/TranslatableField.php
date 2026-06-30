<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;

final class TranslatableField extends AbstractField
{
    private string $innerType = 'text';
    private bool   $managedByModel = false;

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'translatable';
    }

    /**
     * Switch inner field rendering to WYSIWYG editor.
     */
    public function asEditor(): self
    {
        $this->innerType = 'editor';

        return $this;
    }

    /**
     * The inner field type rendered for each locale tab ('text' or 'editor').
     */
    public function innerType(): string
    {
        return $this->innerType;
    }

    /**
     * Explicit opt-out of mutator-side JSON encoding.
     *
     * Use when the host model already serializes the value via an Eloquent cast
     * (Spatie HasTranslations, `'array'` cast, `'json'` cast, custom AsArrayObject).
     * Without this flag the mutator may double-encode.
     */
    public function managedByModel(): self
    {
        $this->managedByModel = true;

        return $this;
    }

    public function isManagedByModel(): bool
    {
        return $this->managedByModel;
    }

    /**
     * Emits `max:<n>` when {@see maxLength()} has been set.
     *
     * {@inheritdoc}
     */
    protected function typeRules(): array
    {
        if ($this->maxLengthValue !== null) {
            return [new ParameterizedRule('max', (string) $this->maxLengthValue)];
        }

        return [];
    }

    /**
     * When locales are provided, emit one dot-notation key per locale
     * (`field.locale` → the field's rules). When locales is empty, fall back
     * to the single flat field-name key (legacy path, preserves current behaviour).
     *
     * @param array<string> $locales
     * @return array<string, list<string>>
     */
    public function expandRules(array $locales, string $context): array
    {
        if ($locales === []) {
            return [$this->name() => array_values($this->ruleSet()->toArray())];
        }

        $fieldRules = array_values($this->ruleSet()->toArray());
        $result     = [];
        foreach ($locales as $locale) {
            $result[$this->name() . '.' . $locale] = $fieldRules;
        }

        return $result;
    }
}
