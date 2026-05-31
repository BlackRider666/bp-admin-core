<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Fields\Concerns\HasFileAllowlist;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;

/**
 * Generic file upload field.
 *
 * SECURITY: if you extend `allowedExtensions(...)` to include script-capable
 * formats such as `svg`, `html`, `htm`, `xml`, `xhtml`, or `swf`, you MUST
 * also call `allowedMimes(...)` to constrain MIME types. Extension-only
 * checks let an attacker upload an `evil.svg` containing an embedded
 * `<script>`; when later served by the download endpoint with the correct
 * `Content-Type` it executes in the same origin and yields stored XSS.
 */
final class FileField extends AbstractField
{
    use HasFileAllowlist;

    /** @var array<int,string> */
    private array $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip'];
    /** @var array<int,string> */
    private array $allowedMimes = [];

    private string $directory = '';
    private string $disk = '';

    public function __construct(
        string $name,
        ?string $label = null,
        array $rules = [],
    ) {
        parent::__construct(name: $name, label: $label, rules: $rules);
        $this->ruleSetInstance->add(Rule::Nullable);
        $this->ruleSetInstance->add(Rule::File);
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'file';
    }

    public function directory(string $dir): self
    {
        $this->directory = $dir;

        return $this;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function disk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function getDisk(): string
    {
        return $this->disk;
    }
}
