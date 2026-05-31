<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields\Concerns;

use BlackParadise\CoreAdmin\Domain\Validation\ParameterizedRule;
use BlackParadise\CoreAdmin\Domain\Validation\RuleSet;

/**
 * Provides extension/MIME allowlist API for file-uploading field types.
 *
 * Using classes must declare the default property values:
 *   /** @var array<int,string> {@*}
 *   private array $allowedExtensions = [...];
 *   /** @var array<int,string> {@*}
 *   private array $allowedMimes = [...];
 *
 * The trait overrides ruleSet() to append mimes:/mimetypes: constraints
 * whenever the allowlists are non-empty. This ensures allowlist constraints
 * survive calls to withRules(), which only resets the base ruleSetInstance.
 */
trait HasFileAllowlist
{
    // $allowedExtensions and $allowedMimes must be declared in the using class
    // with the desired defaults. The trait cannot declare them here because PHP
    // prohibits re-declaring a trait property with different initial values.

    /**
     * Replace the allowed file extensions allowlist.
     *
     * @param array<int,string> $extensions
     */
    public function allowedExtensions(array $extensions): static
    {
        $this->allowedExtensions = array_values($extensions);

        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Replace the allowed MIME types allowlist.
     *
     * @param array<int,string> $mimes
     */
    public function allowedMimes(array $mimes): static
    {
        $this->allowedMimes = array_values($mimes);

        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function getAllowedMimes(): array
    {
        return $this->allowedMimes;
    }

    /**
     * Returns a derived RuleSet that includes the base rules plus any
     * mimes:/mimetypes: constraints from the allowlists.
     *
     * Building a fresh RuleSet here ensures allowlist constraints survive
     * withRules() calls, which only reset the stored ruleSetInstance.
     */
    public function ruleSet(): RuleSet
    {
        $base = parent::ruleSet();

        if (empty($this->allowedExtensions) && empty($this->allowedMimes)) {
            return $base;
        }

        $derived = new RuleSet($base->all());

        if (!empty($this->allowedExtensions)) {
            $derived->add(new ParameterizedRule('mimes', implode(',', $this->allowedExtensions)));
        }

        if (!empty($this->allowedMimes)) {
            $derived->add(new ParameterizedRule('mimetypes', implode(',', $this->allowedMimes)));
        }

        return $derived;
    }

    /**
     * Returns the serialized validation rules array, including allowlist constraints.
     *
     * Delegates to ruleSet()->toArray() so that the overridden ruleSet() above
     * is always consulted (AbstractField::rules() accesses ruleSetInstance directly
     * and would bypass the derived RuleSet).
     *
     * @return array<string>
     */
    public function rules(): array
    {
        return $this->ruleSet()->toArray();
    }
}
