<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Files;

/**
 * Abstraction over the file storage backend.
 *
 * Implementations are responsible for storing uploaded files, deleting
 * stored files by path, and exposing a public URL for a stored file.
 *
 * The optional `$disk` parameter lets callers select a non-default disk
 * (e.g. `s3`, `local`) when a field declares a specific storage location.
 * When `$disk` is null, the implementation falls back to its configured
 * default disk.
 */
interface FileStorageProviderContract
{
    public function store(string $path, mixed $file, ?string $disk = null): string;
    public function delete(string $path, ?string $disk = null): bool;
    public function url(string $path, ?string $disk = null): string;
}
