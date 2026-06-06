<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\ResolveEmbeddedRelationsUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Entity\EntityRecord;
use BlackParadise\CoreAdmin\Domain\Exceptions\ValidationException;
use BlackParadise\CoreAdmin\Domain\Fields\BelongsToField;
use BlackParadise\CoreAdmin\Domain\Fields\HasManyField;
use BlackParadise\CoreAdmin\Domain\Fields\TextField;
use BlackParadise\CoreAdmin\Domain\ValueObjects\EntityKey;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\StubEmbeddedDefinition;

/**
 * B.1 — embedded hasMany child validation must skip the back-FK to host.
 *
 * A7: resolveOnStore with embedded hasMany children passes even when back-FK
 *     field (book_id) is absent from the submitted payload.
 * A8: resolveOnUpdate behaves identically.
 * A9: a genuinely invalid child field (e.g. empty required title) STILL raises
 *     ValidationException — only the back-FK is suppressed, not all rules.
 */
final class EmbeddedHasManyFkSkipTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Model-class constants used as target() on BelongsToField stubs
    // -------------------------------------------------------------------------

    /** Fictional host model class (mirrors what the host definition returns). */
    private const HOST_MODEL = 'App\\Models\\Book';

    /** Fictional child model class. */
    private const CHILD_MODEL = 'App\\Models\\Chapter';

    // -------------------------------------------------------------------------
    // Fixture: child EntityDefinition (Chapter) with a back-FK to Book
    // -------------------------------------------------------------------------

    /**
     * Build an in-memory child definition whose fields() include:
     *   - TextField 'title'         (represents a real child field)
     *   - BelongsToField 'book_id' → HOST_MODEL (the back-FK to the host)
     */
    private function makeChapterDefinition(): EntityDefinitionContract
    {
        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('name')->willReturn('chapters');
        $def->method('keyField')->willReturn('id');
        $def->method('keyType')->willReturn('int');
        $def->method('modelClass')->willReturn(self::CHILD_MODEL);
        $def->method('fields')->willReturn([
            TextField::make('title')->required(),
            BelongsToField::make('book_id', self::HOST_MODEL)->required(),
        ]);
        return $def;
    }

    /**
     * Build the host definition (Book) that owns a HasManyField 'chapters'
     * embedded via StubEmbeddedDefinition (the resolveDefinition closure
     * will return $chapterDef regardless of the class string).
     */
    private function makeBookDefinition(): EntityDefinitionContract
    {
        $def = $this->createMock(EntityDefinitionContract::class);
        $def->method('name')->willReturn('books');
        $def->method('keyField')->willReturn('id');
        $def->method('keyType')->willReturn('int');
        $def->method('modelClass')->willReturn(self::HOST_MODEL);
        $def->method('fields')->willReturn([
            TextField::make('title'),
            HasManyField::make('chapters', self::CHILD_MODEL)
                ->embed(StubEmbeddedDefinition::class),
        ]);
        return $def;
    }

    /**
     * Build a ResolveEmbeddedRelationsUseCase whose validateRecord closure
     * captures the $skipFields argument passed to it.
     *
     * @param array<int, list<string>> $capturedSkips Collects each call's skip list.
     * @param EntityDefinitionContract $chapterDef The embedded definition returned by resolveDefinition.
     */
    private function makeUseCaseCapturingSkips(
        array &$capturedSkips,
        EntityDefinitionContract $chapterDef,
    ): ResolveEmbeddedRelationsUseCase {
        $createClosure = fn(EntityDefinitionContract $def, EntityRecordContract $rec): EntityRecordContract
            => new EntityRecord($def, ['id' => 1] + $rec->attributes());

        $updateClosure = fn(EntityDefinitionContract $def, EntityKey $key, EntityRecordContract $rec): EntityRecordContract
            => new EntityRecord($def, ['id' => $key->value] + $rec->attributes());

        $resolveClosure = fn(string $defClass): EntityDefinitionContract => $chapterDef;

        $validateRecord = function (
            EntityDefinitionContract $def,
            array $attrs,
            array $skip = [],
        ) use (&$capturedSkips): void {
            $capturedSkips[] = $skip;
            // No validation errors — we only want to capture the skip list.
        };

        return new ResolveEmbeddedRelationsUseCase(
            createRecord: $createClosure,
            updateRecord: $updateClosure,
            resolveDefinition: $resolveClosure,
            validateRecord: $validateRecord,
        );
    }

    /**
     * Build a use case whose validateRecord closure throws ValidationException
     * when the child title is empty, regardless of the skip list.
     */
    private function makeUseCaseThrowingOnEmptyTitle(
        EntityDefinitionContract $chapterDef,
    ): ResolveEmbeddedRelationsUseCase {
        $createClosure = fn(EntityDefinitionContract $def, EntityRecordContract $rec): EntityRecordContract
            => new EntityRecord($def, ['id' => 1] + $rec->attributes());

        $updateClosure = fn(EntityDefinitionContract $def, EntityKey $key, EntityRecordContract $rec): EntityRecordContract
            => new EntityRecord($def, ['id' => $key->value] + $rec->attributes());

        $resolveClosure = fn(string $defClass): EntityDefinitionContract => $chapterDef;

        $validateRecord = function (
            EntityDefinitionContract $def,
            array $attrs,
            array $skip = [],
        ): void {
            if (($attrs['title'] ?? '') === '') {
                throw new ValidationException(['title' => ['The title field is required.']]);
            }
        };

        return new ResolveEmbeddedRelationsUseCase(
            createRecord: $createClosure,
            updateRecord: $updateClosure,
            resolveDefinition: $resolveClosure,
            validateRecord: $validateRecord,
        );
    }

    // -------------------------------------------------------------------------
    // A7 — resolveOnStore: back-FK is passed in the skip list
    // -------------------------------------------------------------------------

    /**
     * @test
     * resolveOnStore must pass the back-FK field name ('book_id') in the
     * $skipFields argument to validateRecord, so the validator can ignore it.
     */
    public function test_back_fk_is_skipped_in_child_validation(): void
    {
        $capturedSkips = [];
        $chapterDef    = $this->makeChapterDefinition();
        $bookDef       = $this->makeBookDefinition();
        $useCase       = $this->makeUseCaseCapturingSkips($capturedSkips, $chapterDef);

        $useCase->resolveOnStore($bookDef, [
            'chapters' => [
                ['title' => 'Ch1'],  // back-FK book_id intentionally absent
            ],
        ]);

        self::assertCount(1, $capturedSkips, 'validateRecord must be called once per child.');
        self::assertContains(
            'book_id',
            $capturedSkips[0],
            'The back-FK field "book_id" must appear in the skip list passed to validateRecord.',
        );
    }

    // -------------------------------------------------------------------------
    // A8 — resolveOnUpdate: same skip behaviour on the update path
    // -------------------------------------------------------------------------

    /**
     * @test
     * resolveOnUpdate must also pass the back-FK field name in the skip list.
     */
    public function test_back_fk_is_skipped_in_child_validation_on_update(): void
    {
        $capturedSkips = [];
        $chapterDef    = $this->makeChapterDefinition();
        $bookDef       = $this->makeBookDefinition();
        $useCase       = $this->makeUseCaseCapturingSkips($capturedSkips, $chapterDef);

        $currentHost = new EntityRecord($bookDef, ['id' => 1]);

        $useCase->resolveOnUpdate($bookDef, $currentHost, [
            'chapters' => [
                ['title' => 'Ch1'],
            ],
        ]);

        self::assertCount(1, $capturedSkips, 'validateRecord must be called once per child on update.');
        self::assertContains(
            'book_id',
            $capturedSkips[0],
            'The back-FK field "book_id" must appear in the skip list on update path too.',
        );
    }

    // -------------------------------------------------------------------------
    // A9 — genuinely invalid child field still bubbles (only FK is stripped)
    // -------------------------------------------------------------------------

    /**
     * @test
     * A non-FK required field (title) failing validation must STILL cause a
     * ValidationException with the prefixed key (<relation>.<index>.<field>).
     * The skip mechanism must not suppress real validation errors.
     */
    public function test_other_child_errors_still_bubble(): void
    {
        $chapterDef = $this->makeChapterDefinition();
        $bookDef    = $this->makeBookDefinition();
        $useCase    = $this->makeUseCaseThrowingOnEmptyTitle($chapterDef);

        try {
            $useCase->resolveOnStore($bookDef, [
                'chapters' => [
                    ['title' => ''],  // empty title — must fail
                ],
            ]);
            self::fail('Expected ValidationException was not thrown for an invalid child field.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey(
                'chapters.0.title',
                $e->errors(),
                'Non-FK validation errors must bubble with dot-notation prefix <relation>.<index>.<field>.',
            );
        }
    }
}
