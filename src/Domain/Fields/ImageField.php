<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Fields\Concerns\HasFileAllowlist;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;

/**
 * Image upload field. Defaults to raster formats with matching MIME allowlist.
 *
 * SECURITY: if you widen `allowedExtensions(...)` to include `svg` (vector
 * format with full JavaScript support) or any other script-capable type, you
 * MUST also extend `allowedMimes(...)` accordingly. Extension-only checks let
 * an attacker upload a malicious `evil.svg`; when later served by the
 * download endpoint with the correct `Content-Type` it executes in the same
 * origin and yields stored XSS.
 */
final class ImageField extends AbstractField
{
    use HasFileAllowlist;

    /** @var array<int,string> */
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    /** @var array<int,string> */
    private array $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private string $directory = '';
    private string $disk = '';
    private int $maxWidth = 0;

    public function __construct(
        string $name,
        ?string $label = null,
        array $rules = [],
    ) {
        parent::__construct(name: $name, label: $label, rules: $rules);
        $this->ruleSetInstance->add(Rule::Nullable);
        $this->ruleSetInstance->add(Rule::File);
        $this->ruleSetInstance->add(Rule::Image);
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'image';
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

    public function maxWidth(int $px): self
    {
        $this->maxWidth = $px;

        return $this;
    }

    public function getMaxWidth(): int
    {
        return $this->maxWidth;
    }
}
