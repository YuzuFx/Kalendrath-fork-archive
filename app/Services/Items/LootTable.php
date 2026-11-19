<?php

namespace OGame\Services\Items;

use Illuminate\Support\Collection;
use OGame\Enums\ItemKind;
use OGame\Models\ItemDefinition;
use Random\Randomizer;
use RuntimeException;

/**
 * One loot table of config/loot.php, with the rules of a roll (format
 * documented in that file): attribution probability, eligible pool with
 * effective weights per item, weighted pick. No database write: shared by
 * LootService (real rolls, journaled) and kalendrath:loot:simulate.
 */
final readonly class LootTable
{
    public const string RESULT_GRANTED = 'granted';

    public const string RESULT_NO_DROP = 'no_drop';

    public const string RESULT_EMPTY_POOL = 'empty_pool';

    /**
     * @param array<string, float> $itemWeights base weight per item code
     * @param array<int, float> $rarityMultipliers
     * @param array<string, float> $weights per-table overrides per item code
     * @param list<string>|null $slotTypes
     */
    public function __construct(
        public string $code,
        public string $source,
        public float $dropChance,
        public int $levelMargin,
        public array|null $slotTypes,
        public array $rarityMultipliers,
        public array $weights,
        public array $itemWeights,
        public int $version,
    ) {
    }

    public static function fromConfig(string $code): self
    {
        // Table codes contain dots (expedition.t1): no dot-notation lookup.
        $tables = (array) config('loot.tables');
        $table = $tables[$code] ?? null;
        if (!is_array($table)) {
            throw new RuntimeException('Unknown loot table: ' . $code);
        }

        $levelMargin = $table['level_margin'] ?? null;

        return new self(
            $code,
            (string) ($table['source'] ?? ''),
            (float) ($table['drop_chance'] ?? 0),
            $levelMargin === null ? (int) config('loot.level_margin') : (int) $levelMargin,
            isset($table['slot_types']) && is_array($table['slot_types']) ? array_values(array_map(strval(...), $table['slot_types'])) : null,
            array_map(floatval(...), (array) ($table['rarity_multipliers'] ?? [])),
            array_map(floatval(...), (array) ($table['weights'] ?? [])),
            array_map(floatval(...), (array) config('loot.item_weights')),
            (int) config('loot.version'),
        );
    }

    /**
     * Codes of every configured table.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_values(array_map(strval(...), array_keys((array) config('loot.tables'))));
    }

    /**
     * Short hash of everything that drives a roll of this table: journaled
     * with each roll, so two rolls can be compared even inside one version.
     */
    public function revision(): string
    {
        $weights = [];
        foreach ($this->itemWeights + $this->weights as $code => $unused) {
            $weights[$code] = $this->weights[$code] ?? $this->itemWeights[$code] ?? 0.0;
        }
        ksort($weights);
        $multipliers = $this->rarityMultipliers;
        ksort($multipliers);

        return substr(hash('sha256', (string) json_encode([
            $this->code, $this->dropChance, $this->levelMargin, $this->slotTypes, $multipliers, $weights,
        ])), 0, 16);
    }

    /**
     * Attribution probability once the server multiplier is applied.
     */
    public function attributionChance(float $multiplier): float
    {
        return max(0.0, min(1.0, $this->dropChance * $multiplier));
    }

    /**
     * Effective weight of one definition in this table.
     */
    public function weightOf(ItemDefinition $definition): float
    {
        $base = $this->weights[$definition->code] ?? $this->itemWeights[$definition->code] ?? 0.0;

        return max(0.0, $base * ($this->rarityMultipliers[$definition->rarity] ?? 1.0));
    }

    /**
     * Eligible definitions and their effective weight, in id order.
     *
     * @param iterable<ItemDefinition> $definitions catalog to filter (all definitions)
     * @param list<string> $equipClasses classes the beneficiary can wear
     * @param int $heroLevel level of the beneficiary's main hero (1 without hero)
     * @return list<array{definition: ItemDefinition, weight: float}>
     */
    public function pool(iterable $definitions, array $equipClasses, int $heroLevel): array
    {
        $pool = [];
        foreach ($definitions as $definition) {
            if ($definition->kind !== ItemKind::EQUIPMENT || $definition->retired_at !== null || $definition->slot_type === null) {
                continue;
            }
            if (!in_array($definition->equip_class->value, $equipClasses, true)) {
                continue;
            }
            if ($definition->required_level > $heroLevel + $this->levelMargin) {
                continue;
            }
            if ($this->slotTypes !== null && !in_array($definition->slot_type->value, $this->slotTypes, true)) {
                continue;
            }
            $weight = $this->weightOf($definition);
            if ($weight <= 0.0) {
                continue;
            }
            $pool[] = ['definition' => $definition, 'weight' => $weight];
        }

        usort($pool, fn (array $a, array $b) => $a['definition']->id <=> $b['definition']->id);

        return $pool;
    }

    /**
     * Probability of each definition of a pool (sums to 1 for a non-empty pool).
     *
     * @param list<array{definition: ItemDefinition, weight: float}> $pool
     * @return array<string, float> code => probability
     */
    public static function probabilities(array $pool): array
    {
        $total = array_sum(array_column($pool, 'weight'));
        $probabilities = [];
        foreach ($pool as $entry) {
            $probabilities[$entry['definition']->code] = $total > 0 ? $entry['weight'] / $total : 0.0;
        }

        return $probabilities;
    }

    /**
     * One full roll: attribution, then a weighted pick in the pool.
     *
     * @param list<array{definition: ItemDefinition, weight: float}> $pool
     * @return array{result: string, definition: ItemDefinition|null}
     */
    public function roll(array $pool, float $multiplier, Randomizer $random): array
    {
        $chance = $this->attributionChance($multiplier);
        $dropped = $chance >= 1.0 || ($chance > 0.0 && $random->getFloat(0, 1) < $chance);
        if (!$dropped) {
            return ['result' => self::RESULT_NO_DROP, 'definition' => null];
        }
        if ($pool === []) {
            return ['result' => self::RESULT_EMPTY_POOL, 'definition' => null];
        }

        $total = array_sum(array_column($pool, 'weight'));
        $roll = $random->getFloat(0, $total);
        foreach ($pool as $entry) {
            $roll -= $entry['weight'];
            if ($roll < 0) {
                return ['result' => self::RESULT_GRANTED, 'definition' => $entry['definition']];
            }
        }

        return ['result' => self::RESULT_GRANTED, 'definition' => $pool[count($pool) - 1]['definition']];
    }

    /**
     * Catalog definitions a roll may consider (equipment, not retired).
     *
     * @return Collection<int, ItemDefinition>
     */
    public static function catalog(): Collection
    {
        return ItemDefinition::where('kind', ItemKind::EQUIPMENT->value)->whereNull('retired_at')->orderBy('id')->get()->toBase();
    }
}
