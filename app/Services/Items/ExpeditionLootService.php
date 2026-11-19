<?php

namespace OGame\Services\Items;

use OGame\Enums\ItemSourceType;
use OGame\Models\FleetMission;
use OGame\Models\KingdomItem;

/**
 * Expedition side of the loot: the "GainItems" outcome gives one item to the
 * mission owner when the outcome is resolved (arrival), inside the mission's
 * transaction. The item goes straight to the bag and can be equipped at once
 * (decision of 2026-11-19: no check that the hero is back). One roll per
 * arrival mission: key expedition:{fleet_mission_id}:loot.
 */
class ExpeditionLootService
{
    public const string LOOT_TABLE = 'expedition.t1';

    public function __construct(private readonly LootService $loot)
    {
    }

    public function grant(FleetMission $mission, int|null $seed = null): KingdomItem|null
    {
        return $this->loot->grant(new LootContext(
            ItemSourceType::EXPEDITION,
            (string) $mission->id,
            self::LOOT_TABLE,
            (int) $mission->user_id,
            'expedition:' . $mission->id . ':loot',
            $seed,
        ));
    }
}
