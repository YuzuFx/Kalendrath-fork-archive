<?php

namespace Tests\Feature\Items;

use Illuminate\Support\Facades\Date;
use OGame\Enums\HeroArchetype;
use OGame\Enums\ItemSourceType;
use OGame\Enums\KingdomItemState;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\FleetMission;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Models\Resources;
use OGame\Models\User;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Hero\HeroRosterService;
use OGame\Services\Items\ExpeditionLootService;
use OGame\Services\Items\ItemCatalogService;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Tests\FleetDispatchTestCase;

/**
 * The expedition outcome "GainItems" is reachable and gives one item to the
 * kingdom when the outcome is resolved, once, inside the mission transaction.
 */
class ExpeditionItemLootTest extends FleetDispatchTestCase
{
    use ItemFixtures;

    protected int $missionType = 15;
    protected string $missionName = 'Expedition';

    protected function basicSetup(): void
    {
        $this->planetAddUnit('large_cargo', 50);
        $this->playerSetResearchLevel('astrophysics', 1);
        $this->playerSetResearchLevel('computer_technology', 10);

        $settings = resolve(SettingsService::class);
        foreach (['economy_speed', 'fleet_speed_war', 'fleet_speed_holding', 'fleet_speed_peaceful'] as $key) {
            $settings->set($key, 1);
        }
        $this->planetAddResources(new Resources(0, 0, 100000, 0));

        // Only the item outcome can happen.
        foreach (['dark_matter', 'ships', 'resources', 'delay', 'speedup', 'nothing', 'black_hole', 'pirates', 'aliens', 'merchant'] as $outcome) {
            $settings->set('expedition_weight_' . $outcome, 0);
        }
        $settings->set('expedition_weight_items', 100);
        $settings->set('loot_item_rate_multiplier', '1.0');

        resolve(ItemCatalogService::class)->sync();
    }

    protected function messageCheckMissionArrival(): void
    {
    }

    protected function messageCheckMissionReturn(): void
    {
    }

    private function sendExpedition(bool $assertStatus = true): void
    {
        $units = new UnitCollection();
        $units->addUnit(ObjectService::getUnitObjectByMachineName('large_cargo'), 10);
        $this->sendMissionToPosition16($units, new Resources(0, 0, 0, 0), $assertStatus);
    }

    public function testTheItemArrivesWhenTheOutcomeIsResolvedBeforeTheFleetReturns(): void
    {
        $this->basicSetup();
        $hero = resolve(HeroRosterService::class)->recruitMain(User::findOrFail($this->currentUserId), HeroArchetype::WARRIOR, 'Hero');

        $this->sendExpedition();
        $arrival = FleetMission::where('user_id', $this->currentUserId)->where('mission_type', 15)->whereNull('parent_id')->latest('id')->firstOrFail();

        // Just after the outcome is resolved (end of the holding time), long before the return.
        $this->travelTo(Date::createFromTimestamp($arrival->time_arrival + (int) $arrival->time_holding + 1));
        $this->get('/overview')->assertStatus(200);

        $item = KingdomItem::where('owner_user_id', $this->currentUserId)->firstOrFail();
        $this->assertSame(ItemSourceType::EXPEDITION, $item->source_type);
        $this->assertSame((string) $arrival->id, $item->source_ref);
        $this->assertSame(KingdomItemState::AVAILABLE, $item->state);
        $this->assertSame(1, (int) $arrival->refresh()->processed);
        $return = FleetMission::where('parent_id', $arrival->id)->firstOrFail();
        $this->assertSame(0, (int) $return->processed);

        // Equippable at once: no check that the hero is back (decision of 2026-11-19).
        $worn = resolve(HeroEquipmentService::class)->equip($hero, $item);
        $this->assertSame(KingdomItemState::EQUIPPED, $worn->state);

        $this->assertMessageReceivedAndContains('fleets', 'expeditions', ['Expedition Result']);
    }

    public function testAReplayedResolutionNeverGrantsTheItemTwice(): void
    {
        $this->basicSetup();
        $this->sendExpedition();
        $this->travel(10)->hours();
        $this->get('/overview')->assertStatus(200);

        $arrival = FleetMission::where('user_id', $this->currentUserId)->where('mission_type', 15)->whereNull('parent_id')->latest('id')->firstOrFail();
        $again = resolve(ExpeditionLootService::class)->grant($arrival);

        $this->assertSame(1, KingdomItem::where('owner_user_id', $this->currentUserId)->count());
        $this->assertSame(KingdomItem::where('owner_user_id', $this->currentUserId)->value('id'), $again?->id);
        $this->assertSame(1, ItemOperation::where('idempotency_key', 'expedition:' . $arrival->id . ':loot')->count());
    }

    public function testAFullBagRefusesANewExpedition(): void
    {
        $this->basicSetup();
        config(['items.inventory.capacity' => 1]);
        $this->give($this->currentUserId, $this->definition('test_helm'));
        $before = FleetMission::where('user_id', $this->currentUserId)->count();

        $this->sendExpedition(false);

        $this->assertSame($before, FleetMission::where('user_id', $this->currentUserId)->count());
    }

    public function testNoEligibleItemFallsBackToAFailedExpedition(): void
    {
        $this->basicSetup();
        resolve(SettingsService::class)->set('loot_item_rate_multiplier', '0');

        $this->sendExpedition();
        $this->travel(10)->hours();
        $this->get('/overview')->assertStatus(200);

        $this->assertSame(0, KingdomItem::where('owner_user_id', $this->currentUserId)->count());
        $this->assertSame(0, (int) FleetMission::where('user_id', $this->currentUserId)->where('processed', 0)->count());
    }
}
