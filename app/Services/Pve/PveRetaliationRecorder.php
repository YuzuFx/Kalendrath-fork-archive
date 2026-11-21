<?php

namespace OGame\Services\Pve;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet;

final readonly class PveRetaliationRecorder
{
    public function __construct(
        private PveCampService $camps,
        private PveRetaliationSettings $settings,
    ) {
    }

    /**
     * Record the trigger inside the raid's resolution transaction. These rows
     * are audit records, with no active reservation or executable deadline.
     */
    public function record(FleetMission $raid, BattleResult $result, int $reportId): void
    {
        if ($raid->isInUnion() || $raid->planet_id_from === null || $raid->planet_id_to === null
            || $raid->type_from !== PlanetType::Planet->value || $raid->user_id <= 0
            || count($result->attackerFleetResults) !== 1 || !PveRaidLootService::isVictory($result)) {
            return;
        }

        $army = $result->attackerFleetResults[0];
        if ($army->fleetMissionId !== $raid->id || !$army->hasSurvivors()
            || max($army->lootShare->metal->get(), $army->lootShare->crystal->get(), $army->lootShare->deuterium->get()) < 1) {
            return;
        }

        $camp = $this->camps->campForPlanet($raid->planet_id_to);
        if ($camp === null) {
            return;
        }

        try {
            if ($this->settings->active() === null) {
                return;
            }
        } catch (InvalidArgumentException $exception) {
            report($exception);

            return;
        }

        $target = Planet::whereKey($raid->planet_id_from)
            ->where('user_id', $raid->user_id)
            ->where('galaxy', $raid->galaxy_from)
            ->where('system', $raid->system_from)
            ->where('planet', $raid->position_from)
            ->first();
        if ($target === null) {
            return;
        }

        DB::table('pve_retaliation_intents')->upsert([[
            'source_mission_id' => $raid->id,
            'source_battle_report_id' => $reportId,
            'camp_id' => $camp->id,
            'target_user_id' => $raid->user_id,
            'target_planet_id' => $target->id,
            'target_galaxy' => $target->galaxy,
            'target_system' => $target->system,
            'target_position' => $target->planet,
            'recorded_at' => Date::now(),
        ]], ['source_mission_id'], ['source_mission_id']);
    }
}
