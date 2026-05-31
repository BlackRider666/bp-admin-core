<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use InvalidArgumentException;

/**
 * Read-only display field for values reached via a dot-path through relations,
 * e.g. 'publication.title' shows Publication.title of the related model;
 * 'publication.author.name' shows nested Author.name through Publication.
 *
 * Repository auto-eager-loads the relation prefix. Blade field-display resolves
 * via EntityRecord::getByPath().
 *
 * Not writable — intended for tableHeaderFields / show pages.
 */
final class RelationPathField extends AbstractField
{
    public function __construct(
        string $path,
        ?string $label = null,
    ) {
        if (!str_contains($path, '.')) {
            throw new InvalidArgumentException(
                "RelationPathField: '{$path}' must contain a dot (e.g. 'publication.title').",
            );
        }
        parent::__construct(name: $path, label: $label, visibleOnForm: false, writable: false);
    }

    public static function make(string $path, ?string $label = null): self
    {
        return new self($path, $label);
    }

    public function type(): string
    {
        return 'relation_path';
    }

    public function path(): string
    {
        return $this->name();
    }

    /**
     * Part of the path before the final segment — used as eager-load key
     * (Laravel supports dot-notation in ->with() natively).
     *
     * 'publication.title' → 'publication'
     * 'publication.author.name' → 'publication.author'
     */
    public function relationPrefix(): string
    {
        $pos = strrpos($this->name(), '.');
        return $pos === false ? $this->name() : substr($this->name(), 0, $pos);
    }

    /**
     * The final segment — attribute name on the deepest related model.
     */
    public function attributePath(): string
    {
        $pos = strrpos($this->name(), '.');
        return $pos === false ? '' : substr($this->name(), $pos + 1);
    }
}
