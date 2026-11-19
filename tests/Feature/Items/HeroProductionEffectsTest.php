<?php

namespace Tests\Feature\Items;

use OGame\Enums\HeroArchetype;
use OGame\Enums\TalentEffectScope;
use OGame\Exceptions\HeroRuleException;
use OGame\Factories\PlanetServiceFactory;
use OGame\Models\Hero;
use OGame\Models\HeroTalent;
use OGame\Models\KingdomItem;
use OGame\Models\Planet;
use OGame\Models\Resources;
use OGame\Models\TalentNode;
use OGame\Models\User;
use OGame\Services\Hero\HeroEffectsChanged;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Hero\HeroRosterService;
use OGame\Services\Hero\HeroTalentService;
use OGame\Services\Items\ItemCatalogService;
use OGame\Services\ObjectService;
use OGame\Services\PlanetService;
use Tests\IsolatedAccountTestCase;

/**
 * Hero effects (talents and equipment) REALLY change the production stored
 * on the cities, at the right moment: one test per effect and per path.
 */
class HeroProductionEffectsTest extends IsolatedAccountTestCase
{
    use ItemFixtures;

    private Hero $hero;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([$this->planetService, $this->secondPlanetService] as $planet) {
            $this->assertNotNull($planet);
            foreach (['metal_mine' => 20, 'crystal_mine' => 18, 'deuterium_synthesizer' => 15, 'solar_plant' => 25, 'metal_store' => 12, 'crystal_store' => 12, 'deuterium_store' => 12] as $building => $level) {
                $planet->setObjectLevel(ObjectService::getObjectByMachineName($building)->id, $level, true);
            }
            $planet->addResources(new Resources(0, 0, 0, 0));
            $planet->update();
        }

        $user = User::findOrFail($this->currentUserId);
        $this->hero = resolve(HeroRosterService::class)->recruitMain($user, HeroArchetype::WARRIOR, 'Test Hero');
        $this->hero->level = 10;
        $this->hero->save();
    }

    private function planet(int $index): PlanetService
    {
        $planet = $index === 1 ? $this->planetService : $this->secondPlanetService;
        $this->assertNotNull($planet);

        $fresh = resolve(PlanetServiceFactory::class)->make($planet->getPlanetId(), true);
        $this->assertNotNull($fresh);

        return $fresh;
    }

    /**
     * Stored production rates of a city (what resources accrue at).
     *
     * @return array{metal: int, crystal: int, deuterium: int}
     */
    private function stored(int $index): array
    {
        $row = Planet::findOrFail($this->planet($index)->getPlanetId());

        return ['metal' => (int) $row->metal_production, 'crystal' => (int) $row->crystal_production, 'deuterium' => (int) $row->deuterium_production];
    }

    /**
     * Expected hero bonus of a building at a given percent: floor((mine + planet slot) x % / 100).
     */
    private function expectedBonus(int $index, string $building, string $resource, float $percent): int
    {
        $planet = $this->planet($index);
        $index_ = $planet->getObjectProductionIndex(ObjectService::getGameObjectsWithProductionByMachineName($building));
        $base = $index_->mine->{$resource}->get() + $index_->planet_slot->{$resource}->get();

        return (int) floor($base * $percent / 100);
    }

    private function heroComponent(int $index, string $building, string $resource): int
    {
        $planet = $this->planet($index);

        return (int) $planet->getObjectProductionIndex(ObjectService::getGameObjectsWithProductionByMachineName($building))->hero->{$resource}->get();
    }

    private function talent(string $type, float $value, TalentEffectScope $scope): TalentNode
    {
        return TalentNode::create([
            'archetype' => 'warrior', 'branch' => 'test', 'name' => 'Test', 'tier' => 1, 'position' => 0,
            'point_cost' => 1, 'max_rank' => 5,
            'effect_type' => $type, 'effect_value' => $value, 'effect_scope' => $scope,
        ]);
    }

    private function wearProduction(float $value, string $scope, string $target, string $slot = 'head'): KingdomItem
    {
        $definition = $this->definition('test_prod_' . $slot . '_' . $scope . '_' . str_replace('*', 'all', $target), $slot, 'any', [
            ['type' => 'production_bonus_percent', 'scope' => $scope, 'target' => $target, 'value' => $value],
        ]);

        return resolve(HeroEquipmentService::class)->equip($this->hero, $this->give($this->currentUserId, $definition));
    }

    public function testAKingdomWithoutHeroEffectsHasNoHeroBonus(): void
    {
        $this->assertSame(0, $this->heroComponent(1, 'metal_mine', 'metal'));
        $this->assertSame(0, $this->heroComponent(1, 'crystal_mine', 'crystal'));
    }

    public function testEmpireTalentRaisesTheStoredProductionOfEveryCity(): void
    {
        $before1 = $this->stored(1);
        $before2 = $this->stored(2);

        resolve(HeroTalentService::class)->allocate($this->hero, $this->talent('production_bonus_percent', 4, TalentEffectScope::EMPIRE));

        foreach ([1 => $before1, 2 => $before2] as $index => $before) {
            $bonus = $this->expectedBonus($index, 'metal_mine', 'metal', 4);
            $this->assertGreaterThan(0, $bonus);
            $this->assertSame($bonus, $this->heroComponent($index, 'metal_mine', 'metal'));
            $this->assertSame($before['metal'] + $bonus, $this->stored($index)['metal']);
            $this->assertSame($before['crystal'] + $this->expectedBonus($index, 'crystal_mine', 'crystal', 4), $this->stored($index)['crystal']);
            $this->assertSame($before['deuterium'] + $this->expectedBonus($index, 'deuterium_synthesizer', 'deuterium', 4), $this->stored($index)['deuterium']);
        }
    }

    public function testRespecRemovesTheTalentBonus(): void
    {
        $before = $this->stored(1);
        resolve(HeroTalentService::class)->allocate($this->hero, $this->talent('production_bonus_percent', 4, TalentEffectScope::EMPIRE));

        resolve(HeroTalentService::class)->respec($this->hero);

        $this->assertSame($before, $this->stored(1));
    }

    public function testCityTalentOnlyRaisesTheGovernedCity(): void
    {
        HeroTalent::create(['hero_id' => $this->hero->id, 'talent_node_id' => $this->talent('production_bonus_percent', 5, TalentEffectScope::CITY)->id, 'rank' => 1]);
        $before1 = $this->stored(1);
        $before2 = $this->stored(2);

        resolve(HeroRosterService::class)->appointGovernor($this->hero, $this->planet(1)->getPlanetId());

        $this->assertSame($before1['metal'] + $this->expectedBonus(1, 'metal_mine', 'metal', 5), $this->stored(1)['metal']);
        $this->assertSame($before2, $this->stored(2));
    }

    public function testRemovingTheGovernorRemovesTheCityBonus(): void
    {
        HeroTalent::create(['hero_id' => $this->hero->id, 'talent_node_id' => $this->talent('production_bonus_percent', 5, TalentEffectScope::CITY)->id, 'rank' => 1]);
        $before = $this->stored(1);
        resolve(HeroRosterService::class)->appointGovernor($this->hero, $this->planet(1)->getPlanetId());

        resolve(HeroRosterService::class)->appointGovernor($this->hero, null);

        $this->assertSame($before, $this->stored(1));
    }

    public function testEmpireItemTargetedAtWoodOnlyRaisesWood(): void
    {
        $before = $this->stored(2);

        $this->wearProduction(3, 'empire', 'metal');

        $this->assertSame($before['metal'] + $this->expectedBonus(2, 'metal_mine', 'metal', 3), $this->stored(2)['metal']);
        $this->assertSame($before['crystal'], $this->stored(2)['crystal']);
        $this->assertSame($before['deuterium'], $this->stored(2)['deuterium']);
    }

    public function testCityItemOnlyCountsInTheCityTheHeroGoverns(): void
    {
        $before1 = $this->stored(1);
        $this->wearProduction(2, 'city', 'crystal');
        $this->assertSame($before1, $this->stored(1));

        resolve(HeroRosterService::class)->appointGovernor($this->hero, $this->planet(1)->getPlanetId());

        $this->assertSame($before1['crystal'] + $this->expectedBonus(1, 'crystal_mine', 'crystal', 2), $this->stored(1)['crystal']);
        $this->assertSame(0, $this->heroComponent(2, 'crystal_mine', 'crystal'));
    }

    public function testUnequipRemovesTheItemBonus(): void
    {
        $before = $this->stored(1);
        $item = $this->wearProduction(3, 'empire', '*');
        $this->assertNotSame($before, $this->stored(1));

        resolve(HeroEquipmentService::class)->unequip($this->hero, $item);

        $this->assertSame($before, $this->stored(1));
    }

    public function testTheCapAppliesToTheFinalTotalOfTalentsAndItems(): void
    {
        HeroTalent::create(['hero_id' => $this->hero->id, 'talent_node_id' => $this->talent('production_bonus_percent', 8, TalentEffectScope::CITY)->id, 'rank' => 1]);
        $this->hero->planet_id = $this->planet(1)->getPlanetId();
        $this->hero->save();
        $before = $this->stored(1);

        // 8 (city talent) + 5 (empire item) = 13, capped at 10 on the total.
        resolve(HeroEffectsChanged::class)->apply($this->currentUserId, fn () => null);
        $this->wearProduction(5, 'empire', 'metal');

        $this->assertSame($this->expectedBonus(1, 'metal_mine', 'metal', 10), $this->heroComponent(1, 'metal_mine', 'metal'));
        $this->assertGreaterThan($before['metal'], $this->stored(1)['metal']);
    }

    public function testAdvisorsAddAtMostTheirRatioOfTheCap(): void
    {
        $advisor = resolve(HeroRosterService::class)->recruitAdvisor(User::findOrFail($this->currentUserId), HeroArchetype::STEWARD, 'Steward');
        $advisor->level = 5;
        $advisor->save();
        $node = TalentNode::create([
            'archetype' => 'steward', 'branch' => 'test', 'name' => 'Test', 'tier' => 1, 'position' => 0,
            'point_cost' => 1, 'max_rank' => 5,
            'effect_type' => 'production_bonus_percent', 'effect_value' => 9, 'effect_scope' => TalentEffectScope::EMPIRE,
        ]);
        resolve(HeroTalentService::class)->allocate($advisor, $node);

        // 9 % from an advisor, capped at 10 x 0.5 = 5 %.
        $this->assertSame($this->expectedBonus(1, 'metal_mine', 'metal', 5), $this->heroComponent(1, 'metal_mine', 'metal'));
    }

    public function testResourcesAreSettledAtTheOldRateBeforeTheBonusApplies(): void
    {
        $planet = $this->planet(1);
        $planet->update();
        $start = $planet->metal()->get();
        $oldRate = $this->stored(1)['metal'];

        $this->travel(2)->hours();
        $this->wearProduction(5, 'empire', 'metal');

        // The two hours before equipping accrue at the old rate, not the new one.
        $this->assertEqualsWithDelta($start + 2 * $oldRate, $this->planet(1)->metal()->get(), 2);
        $this->assertGreaterThan($oldRate, $this->stored(1)['metal']);
    }

    public function testRebalancingAWornItemRecalculatesTheWearersProduction(): void
    {
        $this->wearProduction(2, 'empire', 'metal');
        $before = $this->stored(1);

        resolve(ItemCatalogService::class)->upsert('test_prod_head_empire_metal', [
            'kind' => 'equipment', 'slot_type' => 'head', 'equip_class' => 'any',
            'effects' => [['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 6]],
        ]);

        $this->assertSame($this->expectedBonus(1, 'metal_mine', 'metal', 6), $this->heroComponent(1, 'metal_mine', 'metal'));
        $this->assertGreaterThan($before['metal'], $this->stored(1)['metal']);
    }

    public function testFoodIsNotAffectedByProductionBonuses(): void
    {
        $before = Planet::findOrFail($this->planet(1)->getPlanetId())->energy_max;

        $this->wearProduction(5, 'empire', '*');

        $this->assertSame($before, Planet::findOrFail($this->planet(1)->getPlanetId())->energy_max);
        $this->assertSame(0, (int) $this->planet(1)->getObjectProductionIndex(ObjectService::getGameObjectsWithProductionByMachineName('solar_plant'))->hero->energy->get());
    }

    public function testAHeroOnlyGovernsACityOfItsKingdomAndOneHeroPerCity(): void
    {
        $roster = resolve(HeroRosterService::class);
        $advisor = $roster->recruitAdvisor(User::findOrFail($this->currentUserId), HeroArchetype::STEWARD, 'Steward');
        $roster->appointGovernor($this->hero, $this->planet(1)->getPlanetId());

        try {
            $roster->appointGovernor($advisor, $this->planet(1)->getPlanetId());
            $this->fail('Two governors in one city.');
        } catch (HeroRuleException) {
            $this->addToAssertionCount(1);
        }

        $foreign = Planet::where('user_id', '!=', $this->currentUserId)->whereNotNull('user_id')->firstOrFail();
        $this->expectException(HeroRuleException::class);
        $roster->appointGovernor($advisor, $foreign->id);
    }

    public function testAGovernorLosesADeletedCity(): void
    {
        $planetId = $this->planet(2)->getPlanetId();
        resolve(HeroRosterService::class)->appointGovernor($this->hero, $planetId);

        Planet::whereKey($planetId)->delete();

        $this->assertNull($this->hero->refresh()->planet_id);
    }
}
