<?php

declare(strict_types=1);

namespace BlackParadise\CoreAdmin\Domain\Fields;

/**
 * Typed embedding strategy for a relation field.
 *
 * Used by ResolveEmbeddedRelationsUseCase to dispatch persistence logic
 * without string-switching on relationKind(). Derive via RelationKind::embedStrategy().
 */
enum EmbedStrategy
{
    /** Child must be created before the host so the FK id can be written into the host row. */
    case CreateFirst;

    /** Child creation is deferred until after the host is saved (host id becomes child FK). */
    case DeferChild;

    /** Multi-child payload is validated eagerly; RelationWriter persists children independently. */
    case ValidateMany;

    /** No embedding semantics — pivot sync / morph file / non-embedded relations. */
    case None;
}
