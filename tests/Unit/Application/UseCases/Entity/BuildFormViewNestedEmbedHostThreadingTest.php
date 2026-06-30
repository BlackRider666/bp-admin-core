<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCases\Entity;

use BlackParadise\CoreAdmin\Application\UseCases\Entity\BuildFormViewUseCase;
use BlackParadise\CoreAdmin\Domain\Contracts\Auth\AuthorizationProviderContract;
use BlackParadise\CoreAdmin\Domain\Contracts\Fields\FieldContract;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\StubNestedGrandparentDefinition;

/**
 * M1/S2 — 2-level nested-embed host-threading coverage.
 *
 * BuildFormViewUseCase::buildEmbeddedFields() must thread the host model class
 * per nesting level so that back-FK fields are skipped against the CORRECT host
 * at each depth:
 *
 *   Level 0  GrandparentDef  host = App\Models\Grandparent
 *     Level 1  ParentDef     host = App\Models\Parent
 *       Level 2  ChildDef    host = App\Models\Child
 *
 * Implementation reference: BuildFormViewUseCase.php:159-161,177-178
 * (embeddedHostModelClass threaded via $this->decorateField() on recursion).
 */
final class BuildFormViewNestedEmbedHostThreadingTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Shared test infrastructure
    // -------------------------------------------------------------------------

    private AuthorizationProviderContract $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = $this->createMock(AuthorizationProviderContract::class);
        $this->auth->method('can')->willReturn(true);
    }

    /**
     * Execute the use case once and return level-1 embedded field names
     * (from the 'parents' container inside GrandparentDef).
     *
     * @return list<string>
     */
    private function level1FieldNames(): array
    {
        $result = (new BuildFormViewUseCase($this->auth, new StubNestedGrandparentDefinition()))
            ->execute('create');

        /** @var list<FieldContract> $level1 */
        $level1 = $result[0]->meta()['embeddedFields'] ?? [];

        return array_map(static fn(FieldContract $f): string => $f->name(), $level1);
    }

    /**
     * Execute the use case once, drill into the 'children' container inside
     * 'parents', and return level-2 embedded field names.
     *
     * @return list<string>
     */
    private function level2FieldNames(): array
    {
        $result = (new BuildFormViewUseCase($this->auth, new StubNestedGrandparentDefinition()))
            ->execute('create');

        /** @var list<FieldContract> $level1 */
        $level1 = $result[0]->meta()['embeddedFields'] ?? [];

        $childrenContainer = null;
        foreach ($level1 as $f) {
            if ($f->name() === 'children') {
                $childrenContainer = $f;
                break;
            }
        }

        if (!$childrenContainer instanceof FieldContract) {
            self::fail('children container must be present in level-1 embeddedFields');
        }

        /** @var list<FieldContract> $level2 */
        $level2 = $childrenContainer->meta()['embeddedFields'] ?? [];

        return array_map(static fn(FieldContract $f): string => $f->name(), $level2);
    }

    // -------------------------------------------------------------------------
    // Level-1 assertions
    // -------------------------------------------------------------------------

    /**
     * A7 at level-1: 'grandparent_id' (BelongsTo App\Models\Grandparent) must be
     * excluded from the 'parents' embeddedFields — it is the back-FK to the top host.
     */
    public function test_level1_back_fk_to_top_host_is_excluded(): void
    {
        self::assertNotContains(
            'grandparent_id',
            $this->level1FieldNames(),
            'grandparent_id must be filtered from level-1 embeddedFields — back-FK to top host (App\Models\Grandparent)',
        );
    }

    /**
     * Non-FK scalar field 'title' (TextField) must be present in level-1 embeddedFields.
     */
    public function test_level1_scalar_field_is_retained(): void
    {
        self::assertContains(
            'title',
            $this->level1FieldNames(),
            'TextField title must survive FK-skip filter at level-1',
        );
    }

    /**
     * The 'children' HasMany container (not a back-FK) must be present in level-1
     * embeddedFields so it can be recursed into at level-2.
     */
    public function test_level1_nested_container_is_retained(): void
    {
        self::assertContains(
            'children',
            $this->level1FieldNames(),
            'children HasMany container must be present in level-1 embeddedFields',
        );
    }

    // -------------------------------------------------------------------------
    // Level-2 assertions — prove per-level host threading
    // -------------------------------------------------------------------------

    /**
     * A7 at level-2: 'parent_id' (BelongsTo App\Models\Parent) must be excluded
     * from the 'children' embeddedFields — it is the back-FK to the level-1 host.
     *
     * This proves the host was threaded to ParentDef->modelClass() (App\Models\Parent),
     * not left at the top host (App\Models\Grandparent).
     */
    public function test_level2_back_fk_to_level1_host_is_excluded(): void
    {
        self::assertNotContains(
            'parent_id',
            $this->level2FieldNames(),
            'parent_id must be filtered from level-2 embeddedFields — back-FK to level-1 host (App\Models\Parent); proves per-level host threading',
        );
    }

    /**
     * KEY assertion: 'grandparent_id' (BelongsTo App\Models\Grandparent) at level-2
     * must NOT be excluded, because at level-2 the host is App\Models\Parent.
     *
     * If the implementation used the TOP host (App\Models\Grandparent) at all levels,
     * grandparent_id would be wrongly excluded here and this test would FAIL — exposing
     * the bug. The test passing confirms BuildFormViewUseCase.php:177-178 threads the
     * embedded def's own modelClass as the host when recursing.
     */
    public function test_level2_fk_to_grandparent_is_retained_proving_host_threading(): void
    {
        self::assertContains(
            'grandparent_id',
            $this->level2FieldNames(),
            'grandparent_id must be retained in level-2 embeddedFields — at level-2 the host is App\Models\Parent, so a BelongsTo App\Models\Grandparent is NOT a back-FK',
        );
    }

    /**
     * Non-FK scalar field 'name' (TextField) must be present in level-2 embeddedFields.
     */
    public function test_level2_scalar_field_is_retained(): void
    {
        self::assertContains(
            'name',
            $this->level2FieldNames(),
            'TextField name must survive FK-skip filter at level-2',
        );
    }
}
