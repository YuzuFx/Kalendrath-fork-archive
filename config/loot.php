<?php

/*
|--------------------------------------------------------------------------
| Loot tables (versioned in the repository)
|--------------------------------------------------------------------------
|
| Source of truth of item loot in the alpha (no table in database). Bump
| "version" whenever anything below changes: the version AND a revision hash
| of the table actually used are written into every loot operation
| (item_operations.meta) for audit.
|
| A roll, for one loot table (OGame\Services\Items\LootTable):
|
|  1. ATTRIBUTION: an item drops with probability
|       clamp(drop_chance x loot_item_rate_multiplier, 0, 1)
|     (setting of the administration, default 1.0, global lever).
|     Otherwise the result is "no_drop".
|  2. POOL: catalog definitions (equipment, not retired) with an effective
|     weight > 0, wearable by the beneficiary's main hero (equip_class of its
|     archetype; no main hero = union of the alpha archetypes), with
|     required_level <= hero level + level_margin, and a slot type allowed by
|     the table. Empty pool = result "empty_pool" (journaled, no item).
|  3. PICK: one definition, probability = its effective weight / sum of the
|     pool's effective weights. Result "granted".
|
| Effective weight of an item in a table =
|   (table "weights"[code] ?? "item_weights"[code] ?? 0)
|   x (table "rarity_multipliers"[rarity] ?? 1)
| Weights are RELATIVE: only their ratios matter inside a pool. A weight of 0
| removes the item from that source. A rarer item gets a lower weight.
|
| Expedition: the outcome "item" is first drawn among the expedition
| outcomes with the setting expedition_weight_items (single authority on the
| expedition frequency); its table keeps drop_chance = 1.0.
|
| ALL VALUES ARE PROVISIONAL (2026-11-19): to be decided by Gregory with
| `php artisan kalendrath:loot:simulate` before the alpha.
*/
return [
    'version' => 2,

    // Items may drop up to this many levels above the main hero's level.
    // 0 = every item is wearable at once. OPEN QUESTION for Gregory (kept at
    // 2, the lot 1 behaviour). A table may override it ("level_margin").
    'level_margin' => (int) env('LOOT_LEVEL_MARGIN', 2),

    // Base weight of every catalog item (database/catalog/items.php).
    // Common (1): armour 100, cloak 80, jewels 70. Uncommon (2): armour 30,
    // jewels and cloak 20. Every catalog code must appear here (tested).
    'item_weights' => [
        // Warrior (war)
        'head_warden_helm' => 100,
        'shoulders_warden_pauldrons' => 100,
        'chest_warden_hauberk' => 30,
        'wrists_warden_bracers' => 100,
        'hands_warden_gauntlets' => 100,
        'belt_warden_girdle' => 100,
        'legs_warden_greaves' => 30,
        'boots_warden_sabatons' => 100,
        // Sorcerer (wizard)
        'head_adept_hood' => 100,
        'shoulders_adept_mantle' => 100,
        'chest_adept_robe' => 30,
        'wrists_adept_cuffs' => 100,
        'hands_adept_gloves' => 100,
        'belt_adept_sash' => 100,
        'legs_adept_leggings' => 30,
        'boots_adept_slippers' => 100,
        // Common to both (any)
        'neck_traveler_amulet' => 70,
        'neck_oath_pendant' => 20,
        'cloak_traveler_cloak' => 80,
        'cloak_shard_mantle' => 20,
        'ring_copper_band' => 70,
        'ring_mana_signet' => 70,
        'ring_iron_seal' => 20,
    ],

    'tables' => [
        // Expedition outcome "item".
        'expedition.t1' => [
            'source' => 'expedition',
            'drop_chance' => 1.0,
            'level_margin' => null,
            'slot_types' => null,
            'rarity_multipliers' => [],
            'weights' => [],
        ],

        // PvE bot camps, tier 1 (config/pve.php, hostile_camp): one roll per
        // surviving attacking army of a victorious raid. Uncommon items a
        // little more likely than on an expedition.
        'pve_camp.t1' => [
            'source' => 'pve_camp',
            'drop_chance' => 0.25,
            'level_margin' => null,
            'slot_types' => null,
            'rarity_multipliers' => [2 => 1.5],
            'weights' => [],
        ],

        // PvE bot camps, tier 2: prepared for a stronger camp type (none uses
        // it yet): more often, uncommon items favoured.
        'pve_camp.t2' => [
            'source' => 'pve_camp',
            'drop_chance' => 0.35,
            'level_margin' => null,
            'slot_types' => null,
            'rarity_multipliers' => [1 => 0.8, 2 => 1.8],
            'weights' => [],
        ],
    ],

    // Assumptions of `kalendrath:loot:simulate` on a x5 server (real time):
    // source events per real hour for an active player. Expedition: 2 slots,
    // about 1 h 40 per expedition (1 h of exploration + travel at x5).
    // Camp: one victory every 2 h (respawn 72 min at x5 + travel + other
    // players). Override them with --per-hour.
    'simulation' => [
        'events_per_hour' => [
            'expedition' => 1.2,
            'pve_camp' => 0.5,
        ],
    ],
];
