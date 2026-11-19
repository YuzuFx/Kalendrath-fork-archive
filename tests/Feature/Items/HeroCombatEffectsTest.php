<?php

namespace Tests\Feature\Items;

use OGame\Enums\HeroArchetype;
use OGame\Enums\TalentEffectScope;
use OGame\Factories\PlayerServiceFactory;
use OGame\GameMissions\BattleEngine\BattleEngine;
use OGame\GameMissions\BattleEngine\Models\AttackerFleet;
use OGame\GameMissions\BattleEngine\Models\BattleResult;
use OGame\GameMissions\BattleEngine\Models\DefenderFleet;
use OGame\GameMissions\BattleEngine\Models\DefenderFleetResult;
use OGame\GameMissions\BattleEngine\PhpBattleEngine;
use OGame\GameMissions\BattleEngine\RustBattleEngine;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Models\Hero;
use OGame\Models\ItemDefinition;
use OGame\Models\KingdomItem;
use OGame\Models\Resources;
use OGame\Models\TalentNode;
use OGame\Models\User;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Hero\HeroRosterService;
use OGame\Services\Hero\HeroTalentService;
use OGame\Services\Items\ItemCatalogService;
use OGame\Services\ObjectService;
use OGame\Services\PlanetService;
use OGame\Services\PlayerService;
use OGame\Services\SettingsService;
use ReflectionMethod;
use ReflectionProperty;
use Tests\IsolatedAccountTestCase;

/**
 * Hero effects (talents and equipment) REALLY change battles, army by army,
 * identically in the PHP and the Rust engines: one test per combat effect of
 * the starter catalog, the caps on the final total, and the isolation between
 * armies (an ally sent to defend never gets the city owner's hero).
 *
 * Base values used below (no research): Soldier (light_fighter) attack 50,
 * shield 10, structure 4000; Guard (heavy_fighter) attack 150, shield 25,
 * structure 10000; rocket_launcher attack 80, shield 20, structure 2000.
 */
class HeroCombatEffectsTest extends IsolatedAccountTestCase
{
    use ItemFixtures;

    private Hero $attackerHero;

    private PlanetService $defenderPlanet;

    private Hero $defenderHero;

    private PlayerService $ally;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attackerHero = $this->mainHero($this->currentUserId);

        $this->defenderPlanet = $this->createForeignPlanet();
        $defenderId = $this->defenderPlanet->getPlayer()?->getId() ?? 0;
        $this->defenderHero = $this->mainHero($defenderId);

        $allyUser = User::factory()->create();
        $this->ally = resolve(PlayerServiceFactory::class)->make($allyUser->id, true);
    }

    private function mainHero(int $userId): Hero
    {
        $hero = resolve(HeroRosterService::class)->recruitMain(User::findOrFail($userId), HeroArchetype::WARRIOR, 'Hero ' . $userId);
        $hero->level = 30;
        $hero->save();

        return $hero;
    }

    private function talent(Hero $hero, string $type, float $value, TalentEffectScope $scope): void
    {
        $node = TalentNode::create([
            'archetype' => 'warrior', 'branch' => 'test', 'name' => 'Test', 'tier' => 1, 'position' => 0,
            'point_cost' => 1, 'max_rank' => 1,
            'effect_type' => $type, 'effect_value' => $value, 'effect_scope' => $scope,
        ]);
        resolve(HeroTalentService::class)->allocate($hero, $node);
    }

    private function wearCatalogItem(Hero $hero, string $code): KingdomItem
    {
        resolve(ItemCatalogService::class)->sync();
        $item = $this->give($hero->user_id, ItemDefinition::where('code', $code)->firstOrFail());

        return resolve(HeroEquipmentService::class)->equip($hero, $item);
    }

    /**
     * @param array<string, int> $units
     */
    private function units(array $units): UnitCollection
    {
        $collection = new UnitCollection();
        foreach ($units as $machineName => $amount) {
            $collection->addUnit(ObjectService::getUnitObjectByMachineName($machineName), $amount);
        }

        return $collection;
    }

    /**
     * @param array<string, int> $attackerUnits
     * @param array<string, int> $garrison Units stationed on the defender's city.
     * @param array<string, int> $allyUnits Allied army defending the city (none if empty).
     * @param array<string, int> $allyAttackerUnits Allied army joining the attack (none if empty).
     */
    private function engine(string $engine, array $attackerUnits, array $garrison, array $allyUnits = [], array $allyAttackerUnits = []): BattleEngine
    {
        foreach ($garrison as $machineName => $amount) {
            $this->defenderPlanet->addUnit($machineName, $amount);
        }

        $attacker = new AttackerFleet();
        $attacker->units = $this->units($attackerUnits);
        $attacker->player = resolve(PlayerServiceFactory::class)->make($this->currentUserId, true);
        $attacker->fleetMissionId = 0;
        $attacker->ownerId = $this->currentUserId;
        $attacker->cargoResources = new Resources(0, 0, 0, 0);
        $attacker->isInitiator = true;
        $attacker->fleetMission = null;

        $attackers = [$attacker];
        if ($allyAttackerUnits !== []) {
            $joined = new AttackerFleet();
            $joined->units = $this->units($allyAttackerUnits);
            $joined->player = $this->ally;
            $joined->fleetMissionId = 987655;
            $joined->ownerId = $this->ally->getId();
            $joined->cargoResources = new Resources(0, 0, 0, 0);
            $joined->isInitiator = false;
            $joined->fleetMission = null;
            $attackers[] = $joined;
        }

        $defenders = [DefenderFleet::fromPlanet($this->defenderPlanet)];
        if ($allyUnits !== []) {
            $ally = new DefenderFleet();
            $ally->units = $this->units($allyUnits);
            $ally->player = $this->ally;
            $ally->fleetMissionId = 987654;
            $ally->ownerId = $this->ally->getId();
            $ally->fleetMission = null;
            $defenders[] = $ally;
        }

        $settings = resolve(SettingsService::class);

        return $engine === 'php'
            ? new PhpBattleEngine($attackers, $this->defenderPlanet, $defenders, $settings)
            : new RustBattleEngine($attackers, $this->defenderPlanet, $defenders, $settings);
    }

    /**
     * @return array{structural_integrity: int, shield: int, attack: int}
     */
    private function stats(BattleEngine $engine, string $side, int $fleetMissionId, string $machineName): array
    {
        return $engine->armyCombatStats()[$side][$fleetMissionId][$machineName];
    }

    public function testWithoutHeroEffectsTheUpstreamValuesAreUnchanged(): void
    {
        $this->playerSetResearchLevel('weapon_technology', 3);
        $engine = $this->engine('php', ['light_fighter' => 1], ['rocket_launcher' => 1]);

        $player = resolve(PlayerServiceFactory::class)->make($this->currentUserId, true);
        $fighter = ObjectService::getUnitObjectByMachineName('light_fighter');
        $this->assertSame([
            'structural_integrity' => $fighter->properties->structural_integrity->calculate($player)->totalValue,
            'shield' => $fighter->properties->shield->calculate($player)->totalValue,
            'attack' => $fighter->properties->attack->calculate($player)->totalValue,
        ], $this->stats($engine, 'attackers', 0, 'light_fighter'));
        $this->assertSame(65, $this->stats($engine, 'attackers', 0, 'light_fighter')['attack']);
        $this->assertSame(['structural_integrity' => 2000, 'shield' => 20, 'attack' => 80], $this->stats($engine, 'defenders', 0, 'rocket_launcher'));
    }

    public function testEmpireTroopTalentRaisesAttackShieldAndStructureOfTheArmy(): void
    {
        $this->talent($this->attackerHero, 'troop_combat_bonus_percent', 10, TalentEffectScope::EMPIRE);

        $engine = $this->engine('php', ['light_fighter' => 1], ['rocket_launcher' => 1]);

        // floor(base x 10 / 100) added to each property.
        $this->assertSame(['structural_integrity' => 4400, 'shield' => 11, 'attack' => 55], $this->stats($engine, 'attackers', 0, 'light_fighter'));
    }

    public function testCatalogTroopItemRaisesTheArmyAndUnequipRemovesIt(): void
    {
        // head_warden_helm: troop_combat_bonus_percent +1 (empire).
        $item = $this->wearCatalogItem($this->attackerHero, 'head_warden_helm');

        $engine = $this->engine('php', ['heavy_fighter' => 1], ['rocket_launcher' => 1]);
        $this->assertSame(['structural_integrity' => 10100, 'shield' => 25, 'attack' => 151], $this->stats($engine, 'attackers', 0, 'heavy_fighter'));

        resolve(HeroEquipmentService::class)->unequip($this->attackerHero, $item);

        $engine = $this->engine('php', ['heavy_fighter' => 1], []);
        $this->assertSame(['structural_integrity' => 10000, 'shield' => 25, 'attack' => 150], $this->stats($engine, 'attackers', 0, 'heavy_fighter'));
    }

    public function testTroopBonusDoesNotStrengthenDefenses(): void
    {
        $this->talent($this->defenderHero, 'troop_combat_bonus_percent', 10, TalentEffectScope::EMPIRE);

        $engine = $this->engine('php', ['light_fighter' => 1], ['rocket_launcher' => 1, 'light_fighter' => 1]);

        $this->assertSame(['structural_integrity' => 2000, 'shield' => 20, 'attack' => 80], $this->stats($engine, 'defenders', 0, 'rocket_launcher'));
        $this->assertSame(['structural_integrity' => 4400, 'shield' => 11, 'attack' => 55], $this->stats($engine, 'defenders', 0, 'light_fighter'));
    }

    public function testCatalogGarrisonItemOnlyStrengthensTheGovernedCityGarrison(): void
    {
        // shoulders_warden_pauldrons: garrison_defense_bonus_percent +2 (city).
        $this->wearCatalogItem($this->defenderHero, 'shoulders_warden_pauldrons');

        // Not governing the attacked city: nothing.
        $engine = $this->engine('php', ['light_fighter' => 1], ['rocket_launcher' => 1]);
        $this->assertSame(['structural_integrity' => 2000, 'shield' => 20, 'attack' => 80], $this->stats($engine, 'defenders', 0, 'rocket_launcher'));

        resolve(HeroRosterService::class)->appointGovernor($this->defenderHero, $this->defenderPlanet->getPlanetId());

        // Governing it: shield and structure of every garrison unit (troops and defenses), never attack.
        $engine = $this->engine('php', ['light_fighter' => 1], ['light_fighter' => 1]);
        $this->assertSame(['structural_integrity' => 2040, 'shield' => 20, 'attack' => 80], $this->stats($engine, 'defenders', 0, 'rocket_launcher'));
        $this->assertSame(['structural_integrity' => 4080, 'shield' => 10, 'attack' => 50], $this->stats($engine, 'defenders', 0, 'light_fighter'));
    }

    public function testGarrisonBonusNeverReachesAnAttackingArmy(): void
    {
        $this->talent($this->attackerHero, 'garrison_defense_bonus_percent', 10, TalentEffectScope::CITY);
        resolve(HeroRosterService::class)->appointGovernor($this->attackerHero, $this->planetService->getPlanetId());

        $engine = $this->engine('php', ['light_fighter' => 1], ['rocket_launcher' => 1]);

        $this->assertSame(['structural_integrity' => 4000, 'shield' => 10, 'attack' => 50], $this->stats($engine, 'attackers', 0, 'light_fighter'));
    }

    public function testCapsApplyOnceOnTheFinalTotalOfTalentsAndItems(): void
    {
        // 14 (talent) + 1.5 (chest_warden_hauberk) + 1 (helm) = 16.5, capped at 15.
        $this->talent($this->attackerHero, 'troop_combat_bonus_percent', 14, TalentEffectScope::EMPIRE);
        $this->wearCatalogItem($this->attackerHero, 'head_warden_helm');
        $this->wearCatalogItem($this->attackerHero, 'chest_warden_hauberk');

        $engine = $this->engine('php', ['heavy_fighter' => 1], ['rocket_launcher' => 1]);
        $this->assertSame(['structural_integrity' => 11500, 'shield' => 28, 'attack' => 172], $this->stats($engine, 'attackers', 0, 'heavy_fighter'));

        // Garrison axis: 20 (talent, city) capped at 15.
        $this->talent($this->defenderHero, 'garrison_defense_bonus_percent', 20, TalentEffectScope::CITY);
        resolve(HeroRosterService::class)->appointGovernor($this->defenderHero, $this->defenderPlanet->getPlanetId());
        $engine = $this->engine('php', ['heavy_fighter' => 1], ['rocket_launcher' => 1]);
        $this->assertSame(['structural_integrity' => 2300, 'shield' => 23, 'attack' => 80], $this->stats($engine, 'defenders', 0, 'rocket_launcher'));
    }

    public function testATargetedTroopEffectOnlyReachesItsUnit(): void
    {
        $definition = $this->definition('test_soldier_banner', 'cloak', 'any', [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => 'light_fighter', 'value' => 10],
        ]);
        resolve(HeroEquipmentService::class)->equip($this->attackerHero, $this->give($this->currentUserId, $definition));

        $engine = $this->engine('php', ['light_fighter' => 1, 'heavy_fighter' => 1], ['rocket_launcher' => 1]);

        $this->assertSame(55, $this->stats($engine, 'attackers', 0, 'light_fighter')['attack']);
        $this->assertSame(150, $this->stats($engine, 'attackers', 0, 'heavy_fighter')['attack']);
    }

    public function testEachArmyOnlyGetsItsOwnHero(): void
    {
        // The city owner has a strong hero; the ally who came to defend has none.
        $this->talent($this->defenderHero, 'troop_combat_bonus_percent', 10, TalentEffectScope::EMPIRE);
        $this->talent($this->defenderHero, 'garrison_defense_bonus_percent', 10, TalentEffectScope::CITY);
        resolve(HeroRosterService::class)->appointGovernor($this->defenderHero, $this->defenderPlanet->getPlanetId());

        foreach (['php', 'rust'] as $name) {
            $engine = $this->engine($name, ['light_fighter' => 1], ['heavy_fighter' => 1], ['heavy_fighter' => 1]);

            $this->assertSame(['structural_integrity' => 12000, 'shield' => 30, 'attack' => 165], $this->stats($engine, 'defenders', 0, 'heavy_fighter'), $name);
            $this->assertSame(['structural_integrity' => 10000, 'shield' => 25, 'attack' => 150], $this->stats($engine, 'defenders', 987654, 'heavy_fighter'), $name);
            $this->assertSame(['structural_integrity' => 4000, 'shield' => 10, 'attack' => 50], $this->stats($engine, 'attackers', 0, 'light_fighter'), $name);
            $this->defenderPlanet->removeUnits($this->units(['heavy_fighter' => 1]), false);
        }
    }

    public function testRustEngineFeedsEachDefendingArmyWithItsOwnResearchAndHero(): void
    {
        // Upstream defect: every defending army used the city owner's research.
        $this->ally->setResearchLevel('weapon_technology', 5);
        $this->talent($this->defenderHero, 'troop_combat_bonus_percent', 10, TalentEffectScope::EMPIRE);

        $engine = $this->engine('rust', ['light_fighter' => 1], ['heavy_fighter' => 1], ['heavy_fighter' => 1]);
        $engine->simulateBattle();
        $result = (new BattleResult());
        $result->defenderFleetResults = [];
        foreach ((new ReflectionProperty(BattleEngine::class, 'defenders'))->getValue($engine) as $fleet) {
            $result->defenderFleetResults[] = new DefenderFleetResult($fleet->fleetMissionId, $fleet->ownerId, $fleet->units);
        }
        $input = (new ReflectionMethod(RustBattleEngine::class, 'prepareBattleInput'))->invoke($engine, $result);

        $heavy = ObjectService::getUnitObjectByMachineName('heavy_fighter')->id;
        [$garrison, $ally] = $input['defender_fleets'];
        $this->assertSame(165, $garrison->units->{$heavy}->attack_power);
        $this->assertEquals(1100, $garrison->units->{$heavy}->hull_plating);
        $this->assertSame(225, $ally->units->{$heavy}->attack_power);
        $this->assertEquals(1000, $ally->units->{$heavy}->hull_plating);
        $this->assertSame(25, $ally->units->{$heavy}->shield_points);
    }

    public function testBothEnginesFightWithTheHeroBonus(): void
    {
        // Ten Soldiers against one Guard and one allied Guard: no rapid fire
        // between them, so the first-round firepower is exact.
        $this->talent($this->attackerHero, 'troop_combat_bonus_percent', 10, TalentEffectScope::EMPIRE);
        $this->talent($this->defenderHero, 'troop_combat_bonus_percent', 10, TalentEffectScope::EMPIRE);

        foreach (['php', 'rust'] as $name) {
            $result = $this->engine($name, ['light_fighter' => 10], ['heavy_fighter' => 1], ['heavy_fighter' => 1])->simulateBattle();
            $first = $result->rounds[0];

            $this->assertSame(10 * 55, $first->fullStrengthAttacker, $name);
            // Defender's own Guard 165, allied Guard 150 (never the owner's hero).
            $this->assertSame(165 + 150, $first->fullStrengthDefender, $name);
            $this->defenderPlanet->removeUnits($this->units(['heavy_fighter' => 1]), false);
        }
    }

    public function testTwoArmiesOfTheSameUnitTypeKeepTheirOwnValuesInBothEngines(): void
    {
        // Upstream Rust kept one set of values per unit TYPE (the last army's):
        // two attacking armies of Soldiers must each fight with their own.
        $this->talent($this->attackerHero, 'troop_combat_bonus_percent', 10, TalentEffectScope::EMPIRE);

        foreach (['php', 'rust'] as $name) {
            $result = $this->engine($name, ['light_fighter' => 10], ['heavy_fighter' => 1], [], ['light_fighter' => 10])->simulateBattle();

            $this->assertSame(10 * 55 + 10 * 50, $result->rounds[0]->fullStrengthAttacker, $name);
            $this->defenderPlanet->removeUnits($this->units(['heavy_fighter' => 1]), false);
        }
    }
}
