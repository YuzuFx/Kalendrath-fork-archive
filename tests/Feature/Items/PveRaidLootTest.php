<?php

namespace Tests\Feature\Items;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use OGame\Enums\HeroArchetype;
use OGame\Enums\ItemSourceType;
use OGame\Enums\KingdomItemState;
use OGame\Enums\TalentEffectScope;
use OGame\GameMissions\BattleEngine\Models\AttackerFleetResult;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\BattleReport;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Hero;
use OGame\Models\HeroTalent;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Models\Message;
use OGame\Models\Planet;
use OGame\Models\Planet\Coordinate;
use OGame\Models\PveCamp;
use OGame\Models\Resources;
use OGame\Models\TalentNode;
use OGame\Models\User;
use OGame\Services\Hero\HeroBattleReport;
use OGame\Services\Hero\HeroRosterService;
use OGame\Services\Items\ItemCatalogService;
use OGame\Services\Items\PveLaunchGuard;
use OGame\Services\ObjectService;
use OGame\Services\Pve\PveCampService;
use OGame\Services\Pve\PveRaidLootService;
use OGame\Services\Pve\PveRetaliationRecorder;
use OGame\Services\SettingsService;
use Tests\FleetDispatchTestCase;

/**
 * A PvE bot camp is a planet of the bot account, attacked with the upstream
 * attack mission and battle engines. A victorious raid gives item loot per
 * attacking army, at once, once (key pve_raid:{fleet_mission_id}:loot).
 */
class PveRaidLootTest extends FleetDispatchTestCase
{
    use ItemFixtures;

    protected int $missionType = 1;
    protected string $missionName = 'Attack';

    private Coordinate $campCoordinate;

    protected function basicSetup(): void
    {
        $this->planetAddUnit('cruiser', 50);
        $this->planetAddUnit('espionage_probe', 5);
        $this->playerSetResearchLevel('computer_technology', 1);

        $settings = resolve(SettingsService::class);
        foreach (['fleet_speed_war', 'fleet_speed_holding', 'fleet_speed_peaceful'] as $key) {
            $settings->set($key, 1);
        }
        $settings->set('economy_speed', 5);
        $settings->set('attack_block_until', 0);
        // drop_chance 0.25 x 4 = a certain drop, to test the attribution.
        $settings->set('loot_item_rate_multiplier', '4.0');
        $this->planetAddResources(new Resources(0, 0, 1000000, 0));

        // A tiny garrison: 50 Horsemen always win.
        config(['pve.camp_types.hostile_camp.garrison' => ['light_fighter' => 2, 'rocket_launcher' => 1]]);

        resolve(ItemCatalogService::class)->sync();
    }

    protected function messageCheckMissionArrival(): void
    {
    }

    protected function messageCheckMissionReturn(): void
    {
    }

    private function spawnCamp(): PveCamp
    {
        $this->campCoordinate = $this->getNearbyEmptyCoordinate(13, 15);

        return resolve(PveCampService::class)->spawn('hostile_camp', $this->campCoordinate);
    }

    /**
     * @param array<string, int> $units
     */
    private function raid(array $units = ['cruiser' => 50], bool $assertStatus = true): FleetMission|null
    {
        $collection = new UnitCollection();
        foreach ($units as $machineName => $amount) {
            $collection->addUnit(ObjectService::getUnitObjectByMachineName($machineName), $amount);
        }
        $this->dispatchFleet($this->campCoordinate, $collection, new Resources(0, 0, 0, 0), PlanetType::Planet, 0, $assertStatus);

        return FleetMission::where('user_id', $this->currentUserId)->where('mission_type', 1)->whereNull('parent_id')->latest('id')->first();
    }

    private function respawnCommand(): PendingCommand
    {
        $pending = $this->artisan('kalendrath:pve:respawn');
        $this->assertInstanceOf(PendingCommand::class, $pending);

        return $pending;
    }

    private function resolveArrival(FleetMission $mission): void
    {
        $this->travelTo(Date::createFromTimestamp($mission->time_arrival + 1));
        $this->get('/overview')->assertStatus(200);
    }

    public function testASpawnedCampIsABotPlanetWithItsGarrisonAndNoHero(): void
    {
        $this->basicSetup();
        $camp = $this->spawnCamp();

        $planet = Planet::findOrFail($camp->planet_id);
        $bot = User::findOrFail($planet->user_id);
        $this->assertSame('pve-bot@kalendrath.invalid', $bot->email);
        $this->assertSame('[TBD] PvE', $bot->username);
        $this->assertFalse($bot->hasRole('admin'));
        $this->assertSame(0, Hero::where('user_id', $bot->id)->count());
        $this->assertSame(2, (int) $planet->light_fighter);
        $this->assertSame(1, (int) $planet->rocket_launcher);
        $this->assertSame('pve_camp.t1', $camp->loot_table_code);
        // A second camp reuses the same bot account.
        $other = resolve(PveCampService::class)->spawn('hostile_camp', $this->getNearbyEmptyCoordinate(13, 15));
        $this->assertSame($bot->id, Planet::findOrFail($other->planet_id)->user_id);
    }

    public function testAVictoriousRaidGivesTheItemWhenTheBattleIsResolved(): void
    {
        $this->basicSetup();
        $camp = $this->spawnCamp();

        $mission = $this->raid();
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $item = KingdomItem::where('owner_user_id', $this->currentUserId)->firstOrFail();
        $this->assertSame(ItemSourceType::PVE_RAID, $item->source_type);
        $this->assertSame((string) $mission->id, $item->source_ref);
        $this->assertSame(KingdomItemState::AVAILABLE, $item->state);

        // At the resolution of the battle, before the army is back.
        $this->assertSame(1, (int) $mission->refresh()->processed);
        $this->assertSame(0, (int) FleetMission::where('parent_id', $mission->id)->firstOrFail()->processed);

        $operation = ItemOperation::where('idempotency_key', 'pve_raid:' . $mission->id . ':loot')->firstOrFail();
        $this->assertSame('pve_camp.t1', $operation->meta['loot_table'] ?? null);
        $this->assertSame($camp->id, $operation->meta['camp_id'] ?? null);
        $this->assertSame(BattleReport::where('planet_user_id', Planet::findOrFail($camp->planet_id)->user_id)->latest('id')->value('id'), $operation->meta['battle_report_id'] ?? null);

        $camp->refresh();
        $this->assertSame(1, $camp->raids_won);

        // The raider gets one message naming the item (translated when read).
        $messages = Message::where('user_id', $this->currentUserId)->where('key', 'pve_raid_item_found')->get();
        $this->assertCount(1, $messages);
        $this->assertSame($item->definition->code, $messages->firstOrFail()->params['item_code'] ?? null);
        $this->assertMessageReceivedAndContains('fleets', 'combat_reports', [(string) __('t_items.item.' . $item->definition->code . '.name')]);
    }

    public function testVictoryWithResourcesRecordsAnInertIntentOnceEvenWithoutItemLoot(): void
    {
        $this->basicSetup();
        config(['pve.retaliation.enabled' => true]);
        resolve(SettingsService::class)->set('loot_item_rate_multiplier', '0');
        $camp = $this->spawnCamp();

        $mission = $this->raid();
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $intents = DB::table('pve_retaliation_intents')->where('source_mission_id', $mission->id);
        $this->assertDatabaseHas('pve_retaliation_intents', [
            'source_mission_id' => $mission->id,
            'camp_id' => $camp->id,
            'target_user_id' => $this->currentUserId,
            'target_planet_id' => $mission->planet_id_from,
            'target_galaxy' => $mission->galaxy_from,
            'target_system' => $mission->system_from,
            'target_position' => $mission->position_from,
        ]);

        $units = new UnitCollection();
        $units->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 1);
        $army = new AttackerFleetResult($mission->id, $this->currentUserId, $units);
        $army->unitsResult = $units;
        $army->lootShare = new Resources(1, 0, 0, 0);
        $result = new BattleResult();
        $result->attackerFleetResults = [$army];
        $result->attackerUnitsResult = $units;
        $result->defenderUnitsStart = $units;
        $result->defenderUnitsResult = new UnitCollection();

        $report = (int) $intents->value('source_battle_report_id');
        resolve(PveRetaliationRecorder::class)->record($mission, $result, $report + 1);
        $this->assertSame(1, $intents->count());
        $this->assertEquals($report, $intents->value('source_battle_report_id'));
        $this->assertDatabaseCount('kingdom_items', 0);
    }

    public function testDisablingRetaliationKeepsTheOutgoingRaidReward(): void
    {
        $this->basicSetup();
        config(['pve.retaliation.enabled' => false]);
        $this->spawnCamp();

        $mission = $this->raid();
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $this->assertDatabaseMissing('pve_retaliation_intents', ['source_mission_id' => $mission->id]);
        $this->assertDatabaseHas('kingdom_items', ['owner_user_id' => $this->currentUserId, 'source_ref' => (string) $mission->id]);
    }

    public function testTheBattleReportKeepsTheHeroModifiersOfEachArmy(): void
    {
        $this->basicSetup();
        $hero = resolve(HeroRosterService::class)->recruitMain(User::findOrFail($this->currentUserId), HeroArchetype::WARRIOR, 'Raider');
        $node = TalentNode::create([
            'archetype' => 'warrior', 'branch' => 'test', 'name' => 'Test', 'tier' => 1, 'position' => 0,
            'point_cost' => 1, 'max_rank' => 1,
            'effect_type' => 'troop_combat_bonus_percent', 'effect_value' => 10, 'effect_scope' => TalentEffectScope::EMPIRE,
        ]);
        HeroTalent::create(['hero_id' => $hero->id, 'talent_node_id' => $node->id, 'rank' => 1]);
        $this->spawnCamp();

        $mission = $this->raid();
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $report = BattleReport::where('planet_galaxy', $this->campCoordinate->galaxy)->where('planet_system', $this->campCoordinate->system)
            ->where('planet_position', $this->campCoordinate->position)->latest('id')->firstOrFail();
        $snapshot = $report->general[HeroBattleReport::KEY] ?? [];
        $this->assertCount(1, $snapshot, 'Only the raider has a hero bonus (the bot has no hero).');
        $this->assertSame('attackers', $snapshot[0]['side']);
        $this->assertSame($this->currentUserId, $snapshot[0]['owner_id']);
        $this->assertEquals(['attack' => 10, 'shield' => 10, 'structural_integrity' => 10], $snapshot[0]['units']['cruiser']);
    }

    public function testTheRaidWorksWithThePhpEngineToo(): void
    {
        $this->basicSetup();
        resolve(SettingsService::class)->set('battle_engine', 'php');
        try {
            $this->spawnCamp();
            $mission = $this->raid();
            $this->assertNotNull($mission);
            $this->resolveArrival($mission);

            $this->assertSame(1, KingdomItem::where('owner_user_id', $this->currentUserId)->where('source_type', 'pve_raid')->count());
        } finally {
            resolve(SettingsService::class)->set('battle_engine', 'rust');
        }
    }

    public function testALostRaidGivesNothingButTheGarrisonStillRespawns(): void
    {
        $this->basicSetup();
        config(['pve.camp_types.hostile_camp.garrison' => ['rocket_launcher' => 50]]);
        $camp = $this->spawnCamp();

        $mission = $this->raid(['espionage_probe' => 1]);
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $this->assertSame(0, KingdomItem::where('owner_user_id', $this->currentUserId)->count());
        $camp->refresh();
        $this->assertSame(0, $camp->raids_won);
    }

    public function testAnEmptiedCampGivesNoItemUntilItsGarrisonIsBack(): void
    {
        $this->basicSetup();
        $camp = $this->spawnCamp();
        Planet::whereKey($camp->planet_id)->update(['light_fighter' => 0, 'rocket_launcher' => 0]);

        $mission = $this->raid(['cruiser' => 10]);
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $this->assertSame(0, KingdomItem::where('owner_user_id', $this->currentUserId)->count());
        $this->assertSame(0, ItemOperation::where('idempotency_key', 'pve_raid:' . $mission->id . ':loot')->count());
        $this->assertDatabaseMissing('pve_retaliation_intents', ['source_mission_id' => $mission->id]);
    }

    public function testAttackingAPlayerNeverGivesAnItem(): void
    {
        $this->basicSetup();
        $foreign = $this->createForeignPlanet();
        $foreign->addUnit('light_fighter', 1);
        $this->campCoordinate = $foreign->getPlanetCoordinates();

        $mission = $this->raid();
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $this->assertSame(0, KingdomItem::where('owner_user_id', $this->currentUserId)->count());
        $this->assertSame(0, ItemOperation::where('idempotency_key', 'pve_raid:' . $mission->id . ':loot')->count());
        $this->assertDatabaseMissing('pve_retaliation_intents', ['source_mission_id' => $mission->id]);
    }

    public function testAZeroMultiplierJournalsTheRollWithoutAnItem(): void
    {
        $this->basicSetup();
        resolve(SettingsService::class)->set('loot_item_rate_multiplier', '0');
        $this->spawnCamp();

        $mission = $this->raid();
        $this->assertNotNull($mission);
        $this->resolveArrival($mission);

        $this->assertSame(0, KingdomItem::where('owner_user_id', $this->currentUserId)->count());
        $operation = ItemOperation::where('idempotency_key', 'pve_raid:' . $mission->id . ':loot')->firstOrFail();
        $this->assertFalse($operation->meta['dropped'] ?? true);
    }

    public function testAFullBagRefusesARaidOnACampButNotOtherAttacks(): void
    {
        $this->basicSetup();
        config(['items.inventory.capacity' => 1]);
        $this->give($this->currentUserId, $this->definition('test_helm'));
        $this->spawnCamp();

        $this->assertNull($this->raid(['cruiser' => 10], false));

        $this->campCoordinate = $this->createForeignPlanet()->getPlanetCoordinates();
        $this->assertNotNull($this->raid(['cruiser' => 10]));
    }

    public function testARaidInFlightReservesAPlaceAtLaunch(): void
    {
        $this->basicSetup();
        config(['items.inventory.capacity' => 1]);
        $this->spawnCamp();

        $attacks = fn (): int => FleetMission::where('user_id', $this->currentUserId)->where('mission_type', 1)->whereNull('parent_id')->count();

        // Empty bag, nothing in flight: allowed; then the raid holds the only place.
        $this->raid(['cruiser' => 10]);
        $this->assertSame(1, $attacks());
        $this->assertSame(1, resolve(PveLaunchGuard::class)->inFlight($this->currentUserId));
        $this->raid(['cruiser' => 10], false);
        $this->assertSame(1, $attacks());

        // An attack on a player is not PvE content: not counted, not refused.
        $this->campCoordinate = $this->createForeignPlanet()->getPlanetCoordinates();
        $this->raid(['cruiser' => 10]);
        $this->assertSame(2, $attacks());
        $this->assertSame(1, resolve(PveLaunchGuard::class)->inFlight($this->currentUserId));
    }

    /**
     * Union of two armies: one roll per surviving army, never twice.
     */
    public function testOneRollPerSurvivingArmyAndNeverTwice(): void
    {
        $this->basicSetup();
        $camp = $this->spawnCamp();
        $ally = User::factory()->create();

        $survivors = new UnitCollection();
        $survivors->addUnit(ObjectService::getUnitObjectByMachineName('cruiser'), 5);
        $garrison = new UnitCollection();
        $garrison->addUnit(ObjectService::getUnitObjectByMachineName('light_fighter'), 2);

        $mine = new AttackerFleetResult(900001, $this->currentUserId, $survivors);
        $mine->unitsResult = clone $survivors;
        $wiped = new AttackerFleetResult(900002, $ally->id, $survivors);
        $wiped->unitsResult = new UnitCollection();

        $result = new BattleResult();
        $result->attackerFleetResults = [$mine, $wiped];
        $result->attackerUnitsResult = clone $survivors;
        $result->defenderUnitsStart = $garrison;
        $result->defenderUnitsResult = new UnitCollection();

        $service = resolve(PveRaidLootService::class);
        $first = $service->afterBattle($camp->planet_id, $result, null, 42);
        $again = $service->afterBattle($camp->planet_id, $result, null, 42);

        $this->assertCount(1, $first);
        $this->assertSame($first[0]->id, $again[0]->id);
        $this->assertSame(1, KingdomItem::where('owner_user_id', $this->currentUserId)->count());
        $this->assertSame(0, KingdomItem::where('owner_user_id', $ally->id)->count());
        $this->assertSame(0, ItemOperation::where('idempotency_key', 'pve_raid:900002:loot')->count());
    }

    public function testACampHasBuildingsAndProducesResourcesLikeAPlayer(): void
    {
        $this->basicSetup();
        $camp = $this->spawnCamp();
        $planet = Planet::findOrFail($camp->planet_id);
        $this->assertSame(10, (int) $planet->metal_mine);
        $this->assertSame(20000, (int) $planet->metal);

        $this->travel(2)->hours();
        resolve(PveCampService::class)->recruit($camp);

        $this->assertGreaterThan(20000, (int) Planet::findOrFail($camp->planet_id)->metal);
    }

    public function testAPillagedCampRebuildsItsGarrisonOnlyWithItsOwnResources(): void
    {
        $this->basicSetup();
        $camp = $this->spawnCamp();
        // Garrison destroyed and stock looted: nothing to recruit with.
        Planet::whereKey($camp->planet_id)->update(['light_fighter' => 0, 'rocket_launcher' => 0, 'metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'time_last_update' => Date::now()->timestamp]);
        resolve(PveCampService::class)->recordBattle($camp, true);

        $this->assertSame(0, resolve(PveCampService::class)->recruit($camp));
        $this->assertSame(0, (int) Planet::findOrFail($camp->planet_id)->light_fighter);

        // The mines produce; later the camp can pay for its units again.
        $this->travel(6)->hours();
        $this->respawnCommand()->assertSuccessful();
        $planet = Planet::findOrFail($camp->planet_id);
        $this->assertGreaterThan(0, (int) $planet->light_fighter);
        $this->assertLessThanOrEqual(2, (int) $planet->light_fighter);
        $this->assertLessThanOrEqual(1, (int) $planet->rocket_launcher);
    }

    public function testACampNeverRecruitsBeyondItsGarrison(): void
    {
        $this->basicSetup();
        $camp = $this->spawnCamp();

        $this->travel(24)->hours();
        $this->assertSame(0, resolve(PveCampService::class)->recruit($camp));
        $this->assertSame(2, (int) Planet::findOrFail($camp->planet_id)->light_fighter);
    }
}
