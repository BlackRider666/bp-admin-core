<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;

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
}
