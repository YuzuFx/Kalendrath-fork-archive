<?php

namespace OGame\Services\Items;

use Illuminate\Support\Facades\DB;
use OGame\Models\Hero;
use OGame\Models\KingdomItem;

/**
 * Removes a kingdom's Kalendrath rows (items, then heroes) before its users
 * row is deleted: without it, PlayerService::delete() fails on the foreign
 * keys of heroes and kingdom_items (review of 2026-11-19, M6). The item
 * journal is kept on purpose (no foreign key towards users or items).
 */
class KingdomPurgeService
{
    public function purge(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        DB::transaction(function () use ($userId): void {
            // Serialised with loot, launches, equip and discard of this kingdom.
            app(KingdomLock::class)->acquire($userId);
            // Worn items reference their hero (composite key): delete items first.
            KingdomItem::where('owner_user_id', $userId)->delete();
            // Talents and the legacy hero_equipment rows cascade with the hero.
            Hero::where('user_id', $userId)->delete();
        });
    }
}
