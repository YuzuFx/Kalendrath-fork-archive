<?php

namespace OGame\Services\Items;

use OGame\Enums\HeroRole;
use OGame\Enums\ItemOperationType;
use OGame\Models\Hero;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\SettingsService;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Rolls item loot from a versioned table (config/loot.php, rules in
 * LootTable) and gives the item to the beneficiary kingdom: the item fits the
 * main hero's archetype, and its level is at most the hero's level plus the
 * table's level margin (a kingdom without a main hero draws from the classes
 * of the alpha archetypes). One roll at most per idempotency key, even when
 * nothing drops ("no_drop" and "empty_pool" are journaled with the table
 * version and revision), so a replayed resolution never rolls again. Takes
 * the beneficiary's kingdom lock (ItemLedger), after the mission's locks.
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
        return $this->roll($context)['item'];
    }

    /**
     * Same as grant(), and tells whether this call was a replay of an
     * already recorded roll (the caller then sends no second message).
     *
     * @return array{item: KingdomItem|null, replay: bool}
     */
    public function roll(LootContext $context): array
    {
        // NPC players (negative ids) and unknown beneficiaries never loot.
        if ($context->beneficiaryUserId <= 0) {
            return ['item' => null, 'replay' => false];
        }

        $table = LootTable::fromConfig($context->lootTableCode);
        $seed = $context->seed ?? random_int(0, 2147483647);
        $multiplier = $this->settings->lootItemRateMultiplier();
        $meta = [
            'loot_table' => $context->lootTableCode,
            'loot_version' => $table->version,
            'table_revision' => $table->revision(),
            'multiplier' => $multiplier,
            'seed' => $seed,
        ] + $context->meta;

        // The idempotency key is reserved (operation row) before the roll, in
        // the caller's transaction (the mission's): a replay returns the
        // recorded result, "no item" included, and never rolls again.
        [$operation, $replay] = $this->ledger->run(
            $context->idempotencyKey,
            ItemOperationType::LOOT_GRANT,
            $context->beneficiaryUserId,
            ['table' => $context->lootTableCode, 'source_type' => $context->sourceType->value, 'source_ref' => $context->sourceRef],
            function (ItemOperation $operation) use ($context, $table, $seed, $multiplier, $meta): void {
                [$classes, $level] = $this->beneficiaryProfile($context->beneficiaryUserId);
                $pool = $table->pool(LootTable::catalog(), $classes, $level);
                $roll = $table->roll($pool, $multiplier, new Randomizer(new Mt19937($seed)));

                $meta['drop_chance'] = $table->attributionChance($multiplier);
                $meta['hero_level'] = $level;
                $meta['pool_size'] = count($pool);
                $meta['result'] = $roll['result'];
                $meta['dropped'] = $roll['definition'] !== null;

                if ($roll['definition'] !== null) {
                    $item = $this->items->createItem($operation, $context->beneficiaryUserId, $roll['definition'], $context->sourceType, $context->sourceRef);
                    $meta['definition'] = $roll['definition']->code;
                    $meta['rarity'] = $roll['definition']->rarity;
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

        return ['item' => KingdomItem::where('created_operation_id', $operation->id)->first(), 'replay' => $replay];
    }

    /**
     * Who the loot is for: equipment classes and level of the beneficiary's
     * main hero; a kingdom without a main hero draws from the classes of the
     * alpha archetypes, at level 1.
     *
     * @return array{0: list<string>, 1: int}
     */
    public function beneficiaryProfile(int $userId): array
    {
        $hero = Hero::where('user_id', $userId)->where('role', HeroRole::MAIN->value)->first();
        if ($hero !== null) {
            return [$this->equipment->allowedClasses($hero->archetype), $hero->level];
        }

        $classes = [];
        foreach ((array) config('heroes.alpha_archetypes') as $archetype) {
            $classes = array_merge($classes, $this->equipment->allowedClasses((string) $archetype));
        }

        return [array_values(array_unique($classes)), 1];
    }
}
