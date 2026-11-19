<?php

namespace OGame\Services\Items;

use OGame\Enums\HeroRole;
use OGame\Enums\ItemKind;
use OGame\Enums\ItemOperationType;
use OGame\Models\Hero;
use OGame\Models\ItemDefinition;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\SettingsService;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

/**
 * Rolls item loot from a versioned table (config/loot.php) and gives the item
 * to the beneficiary kingdom, ready to equip: the item fits the main hero's
 * archetype and level (a kingdom without a main hero draws from the classes
 * of the alpha archetypes). One roll at most per idempotency key, even when
 * nothing drops (the "no drop" is journaled too), so a replayed resolution
 * never rolls again. Inserts only, no lock: safe inside the mission locks.
 */
class LootService
{
    public function __construct(
        private readonly ItemLedger $ledger,
        private readonly KingdomItemService $items,
        private readonly HeroEquipmentService $equipment,
        private readonly SettingsService $settings,
    ) {
    }

    public function grant(LootContext $context): KingdomItem|null
    {
        // NPC players (negative ids) and unknown beneficiaries never loot.
        if ($context->beneficiaryUserId <= 0) {
            return null;
        }

        // Table codes contain dots (expedition.t1): no dot-notation lookup.
        /** @var array<string, array{drop_chance: float, rarity_weights: array<int, int|float>, level_band: int, slot_types: list<string>|null}> $tables */
        $tables = (array) config('loot.tables');
        $table = $tables[$context->lootTableCode] ?? null;
        if (!is_array($table)) {
            throw new RuntimeException('Unknown loot table: ' . $context->lootTableCode);
        }

        $seed = $context->seed ?? random_int(0, 2147483647);
        $multiplier = $this->settings->lootItemRateMultiplier();
        $meta = [
            'loot_table' => $context->lootTableCode,
            'loot_version' => (int) config('loot.version'),
            'multiplier' => $multiplier,
            'seed' => $seed,
        ] + $context->meta;

        [$operation] = $this->ledger->run(
            $context->idempotencyKey,
            ItemOperationType::LOOT_GRANT,
            $context->beneficiaryUserId,
            ['table' => $context->lootTableCode, 'source_type' => $context->sourceType->value, 'source_ref' => $context->sourceRef],
            function (ItemOperation $operation) use ($context, $table, $seed, $multiplier, $meta): void {
                $random = new Randomizer(new Mt19937($seed));
                $chance = max(0.0, min(1.0, (float) $table['drop_chance'] * $multiplier));
                $meta['drop_chance'] = $chance;

                $dropped = $chance >= 1.0 || ($chance > 0.0 && $random->getFloat(0, 1) < $chance);
                $definition = $dropped ? $this->pick($context->beneficiaryUserId, $table, $random, $meta) : null;
                $meta['dropped'] = $definition !== null;

                if ($definition !== null) {
                    $item = $this->items->createItem($operation, $context->beneficiaryUserId, $definition, $context->sourceType, $context->sourceRef);
                    $meta['definition'] = $definition->code;
                    $meta['item_uid'] = $item->uid;
                }

                $operation->meta = $meta;
                $operation->save();
            },
            null,
            $context->sourceType,
            $context->sourceRef,
            $meta,
        );

        return KingdomItem::where('created_operation_id', $operation->id)->first();
    }

    /**
     * Picks a definition: rarity by weight, then uniformly among the eligible
     * definitions of that rarity (any rarity if none).
     *
     * @param array{drop_chance: float, rarity_weights: array<int, int|float>, level_band: int, slot_types: list<string>|null} $table
     * @param array<string, mixed> $meta
     */
    private function pick(int $userId, array $table, Randomizer $random, array &$meta): ItemDefinition|null
    {
        $hero = Hero::where('user_id', $userId)->where('role', HeroRole::MAIN->value)->first();
        $level = $hero !== null ? $hero->level : 1;
        if ($hero !== null) {
            $classes = $this->equipment->allowedClasses($hero->archetype);
        } else {
            $classes = [];
            foreach ((array) config('heroes.alpha_archetypes') as $archetype) {
                $classes = array_merge($classes, $this->equipment->allowedClasses((string) $archetype));
            }
            $classes = array_values(array_unique($classes));
        }

        $query = ItemDefinition::where('kind', ItemKind::EQUIPMENT->value)
            ->whereNull('retired_at')
            ->where('required_level', '<=', $level + (int) $table['level_band'])
            ->whereIn('equip_class', $classes)
            ->orderBy('id');
        if ($table['slot_types'] !== null) {
            $query->whereIn('slot_type', $table['slot_types']);
        }
        $candidates = $query->get();
        if ($candidates->isEmpty()) {
            $meta['no_candidate'] = true;

            return null;
        }

        $rarity = $this->weighted($table['rarity_weights'], $random);
        $meta['rarity'] = $rarity;
        $ofRarity = $candidates->where('rarity', $rarity)->values();
        $pool = $ofRarity->isEmpty() ? $candidates->values() : $ofRarity;

        /** @var ItemDefinition $picked */
        $picked = $pool[$random->getInt(0, $pool->count() - 1)];

        return $picked;
    }

    /**
     * @param array<int, int|float> $weights
     */
    private function weighted(array $weights, Randomizer $random): int
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return 1;
        }

        $roll = $random->getFloat(0, $total);
        foreach ($weights as $value => $weight) {
            $roll -= $weight;
            if ($roll < 0) {
                return (int) $value;
            }
        }

        return (int) array_key_last($weights);
    }
}
