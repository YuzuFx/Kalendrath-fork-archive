<?php

namespace OGame\Services\Items;

use OGame\Enums\ItemSourceType;

/**
 * What a loot roll needs to know about its source, and nothing else: the
 * loot service ignores whether it is an expedition, a PvE raid or (2027) a
 * quest or an event.
 */
final readonly class LootContext
{
    public function __construct(
        public ItemSourceType $sourceType,
        public string $sourceRef,
        public string $lootTableCode,
        public int $beneficiaryUserId,
        public string $idempotencyKey,
        // Fixed seed for tests; NULL = random (the seed is journaled anyway).
        public int|null $seed = null,
    ) {
    }
}
