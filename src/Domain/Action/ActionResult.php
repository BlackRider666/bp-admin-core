<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Action;

use BlackParadise\CoreAdmin\Domain\Contracts\Action\DownloadContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use InvalidArgumentException;

final readonly class ActionResult
{
    /** @param list<EntityKey> $failedKeys */
    public function __construct(
        public ?string $message = null,
        public ActionOutcome $outcome = ActionOutcome::Success,
        public ?string $redirectUrl = null,
        public ?DownloadContract $download = null,
        public array $failedKeys = [],
    ) {
        foreach ($failedKeys as $key) {
            // @phpstan-ignore instanceof.alwaysTrue (runtime guard for untyped callers)
            if (!$key instanceof EntityKey) {
                throw new InvalidArgumentException('ActionResult failedKeys must be EntityKey instances.');
            }
        }
    }

    public static function message(string $message, ActionOutcome $outcome = ActionOutcome::Success): self
    {
        return new self(message: $message, outcome: $outcome);
    }

    public static function redirect(string $url, ?string $message = null): self
    {
        return new self(message: $message, outcome: ActionOutcome::Success, redirectUrl: $url);
    }

    public static function download(DownloadContract $download): self
    {
        return new self(download: $download);
    }

    /** @param list<EntityKey> $keys */
    public function withFailedKeys(array $keys): self
    {
        return new self($this->message, $this->outcome, $this->redirectUrl, $this->download, $keys);
    }
}
