<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Action;

use BlackParadise\CoreAdmin\Domain\Action\ActionOutcome;
use BlackParadise\CoreAdmin\Domain\Action\ActionResult;
use BlackParadise\CoreAdmin\Domain\Contracts\Action\DownloadContract;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/** A38 — ActionResult VO: message / redirect / download + failedKeys. */
final class ActionResultTest extends TestCase
{
    public function test_message_defaults_to_success_outcome(): void
    {
        $r = ActionResult::message('Done');

        self::assertSame('Done', $r->message);
        self::assertSame(ActionOutcome::Success, $r->outcome);
        self::assertNull($r->redirectUrl);
        self::assertNull($r->download);
    }

    public function test_message_keeps_given_outcome(): void
    {
        $r = ActionResult::message('Careful', ActionOutcome::Warning);

        self::assertSame(ActionOutcome::Warning, $r->outcome);
    }

    public function test_redirect_stores_url_and_optional_message(): void
    {
        $r = ActionResult::redirect('/admin/posts', 'Moved');

        self::assertSame('/admin/posts', $r->redirectUrl);
        self::assertSame('Moved', $r->message);
        self::assertSame(ActionOutcome::Success, $r->outcome);
    }

    public function test_redirect_without_message_has_null_message(): void
    {
        $r = ActionResult::redirect('/admin/posts');

        self::assertNull($r->message);
    }

    public function test_download_stores_download_contract(): void
    {
        $dl = $this->createMock(DownloadContract::class);

        $r = ActionResult::download($dl);

        self::assertSame($dl, $r->download);
        self::assertNull($r->redirectUrl);
    }

    public function test_failed_keys_default_to_empty_list(): void
    {
        self::assertSame([], ActionResult::message('ok')->failedKeys);
    }

    public function test_with_failed_keys_returns_new_instance_carrying_keys(): void
    {
        $original = ActionResult::message('Partial', ActionOutcome::Warning);
        $keys = [new EntityKey(3), new EntityKey(7)];

        $changed = $original->withFailedKeys($keys);

        self::assertNotSame($original, $changed);
        self::assertSame($keys, $changed->failedKeys);
        self::assertSame([], $original->failedKeys);
        self::assertSame('Partial', $changed->message);
    }

    public function test_with_failed_keys_rejects_non_entity_key_elements(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ActionResult::message('x')->withFailedKeys([new EntityKey(1), 'raw']); // @phpstan-ignore argument.type
    }

    public function test_constructor_rejects_non_entity_key_failed_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ActionResult(failedKeys: [42]); // @phpstan-ignore argument.type, new.resultUnused
    }

    public function test_failed_keys_round_trip_entity_keys(): void
    {
        $k = new EntityKey(11);

        self::assertSame([$k], ActionResult::message('x')->withFailedKeys([$k])->failedKeys);
    }

    public function test_class_is_final_readonly(): void
    {
        $ref = new ReflectionClass(ActionResult::class);

        self::assertTrue($ref->isFinal());
        self::assertTrue($ref->isReadOnly());
    }
}
