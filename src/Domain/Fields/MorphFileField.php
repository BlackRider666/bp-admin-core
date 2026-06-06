<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractField;
use BlackParadise\CoreAdmin\Domain\Fields\Concerns\HasFileAllowlist;
use BlackParadise\CoreAdmin\Domain\Validation\Rule;
use RuntimeException;

/**
 * Polymorphic file attachment field.
 *
 * Stores uploaded files not as a column on the host model but as separate
 * records in a normalized files table via a morph relation, e.g.:
 *
 *   MorphFileField::make('avatar')
 *       ->fileModel(File::class)   // required
 *       ->morphName('fileable')    // default: field name
 *       ->storesAs('avatar')       // default: field name
 *       ->directory('avatars')     // optional
 *       ->disk('s3');              // optional
 *
 * Host model must declare: $this->morphOne/morphMany($fileModel, $morphName).
 *
 * SECURITY: if you extend `allowedExtensions(...)` to include script-capable
 * formats such as `svg`, `html`, `htm`, `xml`, `xhtml`, or `swf`, you MUST
 * also call `allowedMimes(...)` to constrain MIME types. Extension-only
 * checks let an attacker upload an `evil.svg` containing an embedded
 * `<script>`; when later served by the download endpoint with the correct
 * `Content-Type` it executes in the same origin and yields stored XSS.
 */
final class MorphFileField extends AbstractField
{
    use HasFileAllowlist;

    /** @var array<int,string> */
    private array $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip'];
    /** @var array<int,string> */
    private array $allowedMimes = [];

    private ?string $morphName = null;
    private ?string $storesAs  = null;
    private ?string $fileModel = null;
    private string  $directory = '';
    private string  $disk      = '';

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function type(): string
    {
        return 'morph_file';
    }

    protected function typeRules(): array
    {
        return [Rule::Nullable, Rule::File];
    }

    public function morphName(string $name): self
    {
        $this->morphName = $name;
        return $this;
    }

    public function getMorphName(): string
    {
        return $this->morphName ?? $this->name();
    }

    public function storesAs(string $type): self
    {
        $this->storesAs = $type;
        return $this;
    }

    public function getStoresAs(): string
    {
        return $this->storesAs ?? $this->name();
    }

    /**
     * Full class-string of the file model (`App\Models\File` or similar).
     * Must be set explicitly — no sensible default without framework config.
     */
    public function fileModel(string $class): self
    {
        $this->fileModel = $class;
        return $this;
    }

    /**
     * @throws RuntimeException if fileModel() was never called.
     */
    public function getFileModel(): string
    {
        if ($this->fileModel === null) {
            throw new RuntimeException(
                "MorphFileField '{$this->name()}' has no fileModel configured — call ->fileModel(YourFile::class).",
            );
        }
        return $this->fileModel;
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
