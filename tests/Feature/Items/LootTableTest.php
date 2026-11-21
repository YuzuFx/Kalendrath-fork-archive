<?php

namespace Tests\Feature\Items;

use Illuminate\Testing\PendingCommand;
use OGame\Models\ItemDefinition;
use OGame\Services\Items\ItemCatalogService;
use OGame\Services\Items\LootTable;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

/**
 * The per-item loot tables of config/loot.php: complete configuration,
 * probabilities that sum to one, filtering by class, level and slot,
 * rarer items less likely, and a distribution that follows the weights.
 */
class LootTableTest extends TestCase
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var array<string, array<string, mixed>> $catalog */
        $catalog = require base_path('database/catalog/items.php');
        $this->catalog = $catalog;
    }

    /**
     * Unsaved definitions built from the catalog file (no database needed).
     *
     * @return list<ItemDefinition>
     */
    private function definitions(): array
    {
        $definitions = [];
        $id = 1;
        foreach ($this->catalog as $code => $entry) {
            $definition = new ItemDefinition();
            $definition->id = $id++;
            $definition->code = $code;
            $definition->kind = $entry['kind'];
            $definition->slot_type = $entry['slot_type'];
            $definition->equip_class = $entry['equip_class'];
            $definition->rarity = $entry['rarity'];
            $definition->required_level = $entry['required_level'];
            $definitions[] = $definition;
        }

        return $definitions;
    }

    public function testEveryCatalogItemHasAWeightAndEveryWeightAnItem(): void
    {
        $codes = array_keys($this->catalog);
        $weights = (array) config('loot.item_weights');

        $this->assertEqualsCanonicalizing($codes, array_keys($weights));
        foreach (LootTable::codes() as $code) {
            $table = LootTable::fromConfig($code);
            $this->assertContains($table->source, ['expedition', 'pve_camp'], $code);
            $this->assertGreaterThanOrEqual(0.0, $table->dropChance, $code);
            $this->assertLessThanOrEqual(1.0, $table->dropChance, $code);
            $this->assertSame([], array_diff(array_keys($table->weights), $codes), $code . ': unknown item in weights');
        }
    }

    public function testPoolProbabilitiesSumToOneForEveryTableArchetypeAndLevel(): void
    {
        foreach (LootTable::codes() as $code) {
            $table = LootTable::fromConfig($code);
            foreach ([['war', 'any'], ['wizard', 'any'], ['war', 'wizard', 'any']] as $classes) {
                foreach (range(1, 30) as $level) {
                    $probabilities = LootTable::probabilities($table->pool($this->definitions(), $classes, $level));
                    $this->assertNotEmpty($probabilities, $code);
                    $this->assertEqualsWithDelta(1.0, array_sum($probabilities), 1e-9, $code);
                }
            }
        }
    }

    public function testThePoolFiltersByClassLevelSlotAndWeight(): void
    {
        $table = LootTable::fromConfig('expedition.t1');
        $codes = fn (array $pool): array => array_map(fn (array $entry) => $entry['definition']->code, $pool);

        // Warrior: heavy and any, never arcane.
        $warrior = $codes($table->pool($this->definitions(), ['war', 'any'], 10));
        $this->assertContains('head_warden_helm', $warrior);
        $this->assertContains('ring_copper_band', $warrior);
        $this->assertNotContains('head_adept_hood', $warrior);

        // Level 1 with a margin of 0: no level 3 item.
        config(['loot.level_margin' => 0]);
        $level1 = $codes(LootTable::fromConfig('expedition.t1')->pool($this->definitions(), ['war', 'any'], 1));
        $this->assertNotContains('chest_warden_hauberk', $level1);
        $this->assertContains('head_warden_helm', $level1);

        // Slot types and a zero weight remove items from a table.
        $rings = new LootTable('test', 'expedition', 1.0, 2, ['ring'], [], ['ring_iron_seal' => 0], (array) config('loot.item_weights'), 1);
        $this->assertSame(['ring_copper_band', 'ring_mana_signet', 'ring_amber_loop'], $codes($rings->pool($this->definitions(), ['any'], 10)));
    }

    public function testARarerItemIsNeverMoreLikelyThanACommonOneOfTheSameSlot(): void
    {
        foreach (LootTable::codes() as $code) {
            $table = LootTable::fromConfig($code);
            $bySlot = [];
            foreach ($this->definitions() as $definition) {
                $bySlot[(string) $definition->slot_type?->value][$definition->rarity][] = $table->weightOf($definition);
            }
            foreach ($bySlot as $slot => $byRarity) {
                if (isset($byRarity[1], $byRarity[2])) {
                    $this->assertLessThan(min($byRarity[1]), max($byRarity[2]), $code . ' / ' . $slot);
                }
            }
        }
    }

    public function testRollsFollowTheAttributionChanceAndTheWeights(): void
    {
        $table = LootTable::fromConfig('pve_camp.t1');
        $pool = $table->pool($this->definitions(), ['war', 'any'], 10);
        $expected = LootTable::probabilities($pool);
        $random = new Randomizer(new Mt19937(42));

        $runs = 40000;
        $granted = 0;
        $counts = [];
        for ($i = 0; $i < $runs; $i++) {
            $roll = $table->roll($pool, 1.0, $random);
            if ($roll['definition'] !== null) {
                $granted++;
                $counts[$roll['definition']->code] = ($counts[$roll['definition']->code] ?? 0) + 1;
            }
        }

        $this->assertEqualsWithDelta(0.25, $granted / $runs, 0.01);
        foreach ($expected as $code => $probability) {
            $this->assertEqualsWithDelta($probability, ($counts[$code] ?? 0) / $granted, 0.015, $code);
        }

        // The server multiplier scales the attribution, clamped to [0, 1].
        $this->assertSame(0.5, $table->attributionChance(2.0));
        $this->assertSame(1.0, $table->attributionChance(10.0));
        $this->assertSame(LootTable::RESULT_NO_DROP, $table->roll($pool, 0.0, $random)['result']);
        $this->assertSame(LootTable::RESULT_EMPTY_POOL, $table->roll([], 4.0, $random)['result']);
    }

    public function testLateGameRaritiesAreRareAndCampsImproveTheOdds(): void
    {
        foreach ([['war', 'any'], ['wizard', 'any']] as $classes) {
            $previousEpicShare = 0.0;
            $previousLegendaryShare = 0.0;
            foreach (LootTable::codes() as $code) {
                $table = LootTable::fromConfig($code);
                $pool = $table->pool($this->definitions(), $classes, 30);
                $probabilities = LootTable::probabilities($pool);
                $expected = [];
                foreach ($pool as $entry) {
                    $definition = $entry['definition'];
                    $rarity = $definition->rarity;
                    $expected[$rarity] = ($expected[$rarity] ?? 0.0) + $probabilities[$definition->code];
                }

                $random = new Randomizer(new Mt19937(20261121));
                $counts = array_fill(1, 5, 0);
                for ($i = 0; $i < 100000; $i++) {
                    $definition = $table->roll($pool, 1.0, $random)['definition'];
                    if ($definition !== null) {
                        $counts[$definition->rarity]++;
                    }
                }
                $granted = array_sum($counts);
                foreach ($counts as $rarity => $count) {
                    $this->assertGreaterThan(0, $count, $code);
                    $this->assertEqualsWithDelta($expected[$rarity], $count / $granted, 0.006, $code);
                }
                $this->assertLessThan(0.05, $expected[4], $code);
                $this->assertLessThan(0.01, $expected[5], $code);
                $this->assertGreaterThan($previousEpicShare, $expected[4], $code);
                $this->assertGreaterThan($previousLegendaryShare, $expected[5], $code);
                $previousEpicShare = $expected[4];
                $previousLegendaryShare = $expected[5];
            }
        }
    }

    public function testTheRevisionChangesWithTheTableContent(): void
    {
        $before = LootTable::fromConfig('expedition.t1')->revision();
        $this->assertSame($before, LootTable::fromConfig('expedition.t1')->revision());

        config(['loot.item_weights.head_warden_helm' => 99]);

        $this->assertNotSame($before, LootTable::fromConfig('expedition.t1')->revision());
    }

    public function testTheSyncedCatalogMatchesTheLootWeights(): void
    {
        resolve(ItemCatalogService::class)->sync();

        foreach (LootTable::catalog() as $definition) {
            $this->assertArrayHasKey($definition->code, (array) config('loot.item_weights'));
        }
    }

    public function testTheSimulationCommandPrintsTheDistributionAndTheHourlyRate(): void
    {
        resolve(ItemCatalogService::class)->sync();

        $camp = $this->artisan('kalendrath:loot:simulate', ['--source' => 'pve_camp', '--tier' => 1, '--runs' => 2000, '--archetype' => 'sorcerer', '--level' => 3]);
        $this->assertInstanceOf(PendingCommand::class, $camp);
        $camp->expectsOutputToContain('Table pve_camp.t1')
            ->expectsOutputToContain('head_adept_hood')
            ->expectsOutputToContain('items per real hour')
            ->expectsOutputToContain('First immediately wearable item')
            ->assertSuccessful()
            ->run();

        $expedition = $this->artisan('kalendrath:loot:simulate', ['--source' => 'expedition', '--runs' => 2000]);
        $this->assertInstanceOf(PendingCommand::class, $expedition);
        $expedition->expectsOutputToContain('item outcome')->assertSuccessful()->run();

        $unknown = $this->artisan('kalendrath:loot:simulate', ['--table' => 'nope.t9']);
        $this->assertInstanceOf(PendingCommand::class, $unknown);
        $unknown->assertFailed()->run();
    }
}
