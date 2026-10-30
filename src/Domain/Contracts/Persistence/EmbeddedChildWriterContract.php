<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Contracts\Persistence;

use BlackParadise\CoreAdmin\Domain\Contracts\Entity\EntityRecordContract;
use BlackParadise\CoreAdmin\Domain\Contracts\EntityDefinition\EntityDefinitionContract;
use BlackParadise\CoreAdmin\Domain\Fields\Base\AbstractRelationField;

interface EmbeddedChildWriterContract
{
    /**
     * Writes all deferred embedded child payloads for a persisted host record.
     *
     * @param array<string, array{field: AbstractRelationField, payload: array<string, mixed>}> $defer
     */
    public function writeAll(EntityDefinitionContract $hostDefinition, EntityRecordContract $host, array $defer): void;
}
