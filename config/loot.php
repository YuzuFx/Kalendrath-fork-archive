<?php

/*
|--------------------------------------------------------------------------
| Loot tables (versioned in the repository)
|--------------------------------------------------------------------------
|
| Source of truth of item loot in the alpha (no table in database).
| Bump "version" whenever a table changes: it is written into every loot
| operation (item_operations.meta) for audit.
|
| - drop_chance: probability that an item drops once the source rolled for
|   loot; multiplied by the server setting loot_item_rate_multiplier
|   (admin, default 1.0), then clamped to [0, 1].
| - rarity_weights: relative weights per EquipmentRarity value.
| - level_band: items up to (main hero level + band) may drop.
| - slot_types: EquipmentSlotType values allowed, or null for all.
|
| ALL VALUES ARE PROVISIONAL: to be calibrated for a x5 server with the
| theorycrafting simulator (tools/theorycrafting/) before the alpha.
*/
return [
    'version' => 1,

    'tables' => [
        // Expedition outcome "GainItems": the outcome itself is already a
        // weighted roll (setting expedition_weight_items), so the drop is
        // certain once the outcome is drawn.
        'expedition.t1' => [
            'drop_chance' => 1.0,
            'rarity_weights' => [1 => 70, 2 => 25, 3 => 5],
            'level_band' => 2,
            'slot_types' => null,
        ],

        // PvE bot camps (config/pve.php): one roll per surviving attacking
        // army of a victorious raid (PveRaidLootService).
        'pve_camp.t1' => [
            'drop_chance' => 0.25,
            'rarity_weights' => [1 => 75, 2 => 22, 3 => 3],
            'level_band' => 2,
            'slot_types' => null,
        ],
    ],
];
