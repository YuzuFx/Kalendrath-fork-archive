<?php

namespace OGame\Console\Commands\Items;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Items\LootTable;
use OGame\Services\SettingsService;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Monte Carlo simulation of one loot table (config/loot.php), with the real
 * rules of LootTable and the synchronised catalog, without writing anything:
 * distribution by item, rarity and slot type (theoretical and observed), and
 * the pieces a player can expect per real hour on the x5 server. Use it to
 * decide the per-item weights and drop chances before changing the file.
 *
 *   php artisan kalendrath:loot:simulate --source=expedition --runs=100000
 *   php artisan kalendrath:loot:simulate --source=pve_camp --tier=1 --archetype=sorcerer --level=3
 */
#[Description('Simulate a loot table: distribution per item, rarity and slot, and items per real hour at x5.')]
#[Signature('kalendrath:loot:simulate
    {--source=expedition : expedition | pve_camp}
    {--tier=1 : Camp tier (pve_camp.tN)}
    {--table= : Explicit table code (overrides --source/--tier)}
    {--runs=100000 : Number of simulated source events}
    {--archetype=warrior : Main hero archetype (warrior | sorcerer | none)}
    {--level=1 : Main hero level}
    {--per-hour= : Source events per real hour (default: config loot.simulation)}
    {--seed=1 : Random seed (reproducible output)}
    {--locale=fr : Language of the item names (the alpha is in French)}')]
class SimulateLoot extends Command
{
    public function handle(SettingsService $settings, HeroEquipmentService $equipment): int
    {
        app()->setLocale((string) $this->option('locale'));
        $source = (string) $this->option('source');
        $code = (string) ($this->option('table') ?: ($source === 'expedition' ? 'expedition.t1' : 'pve_camp.t' . (int) $this->option('tier')));
        if (!in_array($code, LootTable::codes(), true)) {
            $this->error('Unknown loot table: ' . $code . ' (known: ' . implode(', ', LootTable::codes()) . ').');

            return Command::FAILURE;
        }

        $table = LootTable::fromConfig($code);
        $catalog = LootTable::catalog();
        if ($catalog->isEmpty()) {
            $this->error('The item catalog is empty: run php artisan kalendrath:items:sync first.');

            return Command::FAILURE;
        }

        $archetype = (string) $this->option('archetype');
        $classes = [];
        foreach ($archetype === 'none' ? (array) config('heroes.alpha_archetypes') : [$archetype] as $name) {
            $classes = array_merge($classes, $equipment->allowedClasses((string) $name));
        }
        $classes = array_values(array_unique($classes));
        $level = max(1, (int) $this->option('level'));
        $runs = max(1, (int) $this->option('runs'));
        $multiplier = $settings->lootItemRateMultiplier();

        // Expedition: the item outcome is first drawn among the outcomes.
        $outcomeChance = 1.0;
        if ($table->source === 'expedition') {
            $outcomeChance = $this->expeditionItemOutcomeChance($settings);
        }

        $pool = $table->pool($catalog, $classes, $level);
        $expected = LootTable::probabilities($pool);
        $attribution = $table->attributionChance($multiplier);
        $perEvent = $pool === [] ? 0.0 : $outcomeChance * $attribution;

        $random = new Randomizer(new Mt19937((int) $this->option('seed')));
        $observed = [];
        $granted = 0;
        for ($i = 0; $i < $runs; $i++) {
            if ($outcomeChance < 1.0 && $random->getFloat(0, 1) >= $outcomeChance) {
                continue;
            }
            $roll = $table->roll($pool, $multiplier, $random);
            if ($roll['definition'] !== null) {
                $granted++;
                $observed[$roll['definition']->code] = ($observed[$roll['definition']->code] ?? 0) + 1;
            }
        }

        $this->info(sprintf('Table %s (source %s), loot version %d, revision %s', $code, $table->source, $table->version, $table->revision()));
        $this->line(sprintf(
            'Profile: %s, level %d, classes [%s], level margin %d | drop_chance %.3f x multiplier %.2f = attribution %.3f%s',
            $archetype,
            $level,
            implode(', ', $classes),
            $table->levelMargin,
            $table->dropChance,
            $multiplier,
            $attribution,
            $table->source === 'expedition' ? sprintf(' | item outcome %.2f %% of expeditions', 100 * $outcomeChance) : '',
        ));

        $rows = [];
        $byRarity = [];
        $bySlot = [];
        foreach ($pool as $entry) {
            $definition = $entry['definition'];
            $theoretical = $expected[$definition->code];
            $share = $granted > 0 ? ($observed[$definition->code] ?? 0) / $granted : 0.0;
            $rows[] = [
                $definition->code,
                __('t_items.item.' . $definition->code . '.name'),
                $definition->slot_type?->value,
                $definition->rarity,
                $definition->required_level,
                $entry['weight'],
                sprintf('%.2f %%', 100 * $theoretical),
                sprintf('%.2f %%', 100 * $share),
                sprintf('%.3f %%', 100 * $theoretical * $perEvent),
            ];
            $byRarity[$definition->rarity] = [($byRarity[$definition->rarity][0] ?? 0.0) + $theoretical, ($byRarity[$definition->rarity][1] ?? 0.0) + $share];
            $slot = (string) $definition->slot_type?->value;
            $bySlot[$slot] = [($bySlot[$slot][0] ?? 0.0) + $theoretical, ($bySlot[$slot][1] ?? 0.0) + $share];
        }
        $this->table(['Item', 'Name', 'Slot', 'Rarity', 'Level', 'Weight', 'Share (theory)', 'Share (observed)', 'Per event'], $rows);

        ksort($byRarity);
        $this->table(['Rarity', 'Share (theory)', 'Share (observed)'], array_map(
            fn (int $rarity, array $shares) => [$rarity . ' (' . __('t_items.rarity.' . $rarity) . ')', sprintf('%.2f %%', 100 * $shares[0]), sprintf('%.2f %%', 100 * $shares[1])],
            array_keys($byRarity),
            $byRarity,
        ));
        ksort($bySlot);
        $this->table(['Slot type', 'Share (theory)', 'Share (observed)'], array_map(
            fn (string $slot, array $shares) => [$slot, sprintf('%.2f %%', 100 * $shares[0]), sprintf('%.2f %%', 100 * $shares[1])],
            array_keys($bySlot),
            $bySlot,
        ));

        $perHourOption = $this->option('per-hour');
        $eventsPerHour = $perHourOption !== null && $perHourOption !== ''
            ? (float) $perHourOption
            : (float) config('loot.simulation.events_per_hour.' . $table->source, 1.0);
        $itemsPerHour = $eventsPerHour * $perEvent;

        $this->line(sprintf('Items per source event: %.4f (theory), %.4f (observed over %d events, %d items)', $perEvent, $granted / $runs, $runs, $granted));
        $this->line(sprintf(
            'At x5, %.2f %s per real hour: %.3f items per real hour, %.2f per 8 h of active play, 32 places filled in about %s h of active play.',
            $eventsPerHour,
            $table->source === 'expedition' ? 'expeditions' : 'victorious raids',
            $itemsPerHour,
            $itemsPerHour * 8,
            $itemsPerHour > 0 ? number_format(32 / $itemsPerHour, 1) : 'never',
        ));
        if ($pool === []) {
            $this->warn('Empty pool for this profile: every roll would be "empty_pool".');
        }

        return Command::SUCCESS;
    }

    /**
     * Share of the "item" outcome among the expedition outcome weights
     * (same weights as ExpeditionMission, before the Discoverer class bonus).
     */
    private function expeditionItemOutcomeChance(SettingsService $settings): float
    {
        $weights = [
            $settings->expeditionWeightShips(),
            $settings->expeditionWeightResources(),
            $settings->expeditionWeightDelay(),
            $settings->expeditionWeightSpeedup(),
            $settings->expeditionWeightNothing(),
            $settings->expeditionWeightBlackHole(),
            $settings->expeditionWeightPirates(),
            $settings->expeditionWeightAliens(),
            $settings->expeditionWeightDarkMatter(),
            $settings->expeditionWeightMerchant(),
            $settings->expeditionWeightItems(),
        ];
        $total = array_sum(array_map(fn (float $weight) => max(0.0, $weight), $weights));

        return $total > 0 ? max(0.0, $settings->expeditionWeightItems()) / $total : 0.0;
    }
}
