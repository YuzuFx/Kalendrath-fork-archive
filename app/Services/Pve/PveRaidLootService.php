<?php

namespace OGame\Services\Pve;

use OGame\Enums\ItemSourceType;
use OGame\Factories\PlayerServiceFactory;
use OGame\GameMessages\PveRaidItemFound;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\Models\KingdomItem;
use OGame\Models\Planet;
use OGame\Services\Items\LootContext;
use OGame\Services\Items\LootService;
use OGame\Services\MessageService;

/**
 * Raid side of the item loot (alpha lot 2): after a battle against a PvE
 * camp, inside the attack mission's transaction, each attacking army of a
 * victorious raid rolls once in the camp's loot table. The item is given at
 * once (when the battle is resolved, not when the army is back), with the
 * key pve_raid:{fleet_mission_id}:loot, the soft cap of the bag and the
 * loot_item_rate_multiplier setting, exactly like the expedition loot.
 *
 * Victory = the camp had a garrison at the start of the battle, it has no
 * unit left, the attackers have survivors and did not withdraw. An army
 * wiped out in a victorious union rolls nothing. Attacking a planet that is
 * not a camp never gives an item (no item loot in PvP).
 */
class PveRaidLootService
{
    public function __construct(
        private readonly PveCampService $camps,
        private readonly LootService $loot,
        private readonly MessageService $messages,
        private readonly PlayerServiceFactory $players,
    ) {
    }

    public static function isVictory(BattleResult $result): bool
    {
        return !$result->tacticalRetreatAttackerAlsoRetreated
            && $result->defenderUnitsStart->getAmount() > 0
            && $result->defenderUnitsResult->getAmount() === 0
            && $result->attackerUnitsResult->getAmount() > 0;
    }

    /**
     * @param int|null $seed Fixed seed for tests.
     * @return list<KingdomItem> Items given by this battle (replays included).
     */
    public function afterBattle(int $defenderPlanetId, BattleResult $result, int|null $reportId = null, int|null $seed = null): array
    {
        $camp = $this->camps->campForPlanet($defenderPlanetId);
        if ($camp === null || $result->defenderUnitsStart->getAmount() === 0) {
            return [];
        }

        $victory = self::isVictory($result);
        $this->camps->recordBattle($camp, $victory);
        if (!$victory) {
            return [];
        }

        $granted = [];
        foreach ($result->attackerFleetResults as $fleetResult) {
            if (!$fleetResult->hasSurvivors()) {
                continue;
            }

            $roll = $this->loot->roll(new LootContext(
                ItemSourceType::PVE_RAID,
                (string) $fleetResult->fleetMissionId,
                $camp->loot_table_code,
                $fleetResult->playerId,
                'pve_raid:' . $fleetResult->fleetMissionId . ':loot',
                $seed,
                ['camp_id' => $camp->id, 'planet_id' => $defenderPlanetId, 'battle_report_id' => $reportId],
            ));
            $item = $roll['item'];
            if ($item !== null) {
                $granted[] = $item;
                if (!$roll['replay']) {
                    $this->announce($fleetResult->playerId, $item, $defenderPlanetId);
                }
            }
        }

        return $granted;
    }

    /**
     * Message to the raider naming the item won (sent in the mission's
     * transaction, once: never on a replayed roll).
     */
    private function announce(int $userId, KingdomItem $item, int $campPlanetId): void
    {
        $planet = Planet::find($campPlanetId);
        $coordinates = $planet !== null ? '[coordinates]' . $planet->galaxy . ':' . $planet->system . ':' . $planet->planet . '[/coordinates]' : '';

        $this->messages->sendSystemMessageToPlayer($this->players->make($userId), PveRaidItemFound::class, [
            'item_code' => $item->definition->code,
            'coordinates' => $coordinates,
        ]);
    }
}
