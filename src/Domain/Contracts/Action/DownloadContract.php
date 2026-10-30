<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Action;

interface DownloadContract
{
    public function filename(): string;

    public function mimeType(): string;

    /** Streams the download body to the output. */
    public function stream(): void;
}
