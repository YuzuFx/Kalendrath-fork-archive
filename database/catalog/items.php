<?php

/*
|--------------------------------------------------------------------------
| Item catalog (source of truth, versioned in the repository)
|--------------------------------------------------------------------------
|
| Synchronised into item_definitions / item_definition_effects by
| `php artisan kalendrath:items:sync` (idempotent upsert by code). A changed
| entry bumps the definition version, is journaled (definition_rebalance)
| and every kingdom wearing the item has its production settled then
| recalculated. Never remove an entry to delete an item: a missing code is
| retired (it leaves the loot tables, owned copies stay valid).
|
| ALPHA STARTER CATALOG, ALL VALUES PROVISIONAL (2026-11-19): two equipment
| classes (war = Warrior, wizard = Sorcerer) plus "any" jewels and cloaks,
| a few items per slot type, cautious values well under the axis caps.
| Displayed names are provisional (resources/lang/{fr,en}/t_items.php).
| troop_combat_bonus_percent (troops of the army) and
| garrison_defense_bonus_percent (garrison of any city of the kingdom when it
| is attacked) apply in battle army by army since lot 2
| (OGame\Services\Hero\HeroCombatEffects). Every effect is kingdom-wide
| ("empire" scope): no governor per city since 2026-11-19.
*/
return [
    'head_warden_helm' => [
        'kind' => 'equipment', 'slot_type' => 'head', 'equip_class' => 'war', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 1.0],
        ],
    ],
    'shoulders_warden_pauldrons' => [
        'kind' => 'equipment', 'slot_type' => 'shoulders', 'equip_class' => 'war', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 2.0],
        ],
    ],
    'chest_warden_hauberk' => [
        'kind' => 'equipment', 'slot_type' => 'chest', 'equip_class' => 'war', 'rarity' => 2, 'required_level' => 3,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 1.5],
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 1.0],
        ],
    ],
    'wrists_warden_bracers' => [
        'kind' => 'equipment', 'slot_type' => 'wrists', 'equip_class' => 'war', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 1.0],
        ],
    ],
    'hands_warden_gauntlets' => [
        'kind' => 'equipment', 'slot_type' => 'hands', 'equip_class' => 'war', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 1.0],
        ],
    ],
    'belt_warden_girdle' => [
        'kind' => 'equipment', 'slot_type' => 'belt', 'equip_class' => 'war', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 1.5],
        ],
    ],
    'legs_warden_greaves' => [
        'kind' => 'equipment', 'slot_type' => 'legs', 'equip_class' => 'war', 'rarity' => 2, 'required_level' => 3,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 2.0],
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 0.5],
        ],
    ],
    'boots_warden_sabatons' => [
        'kind' => 'equipment', 'slot_type' => 'boots', 'equip_class' => 'war', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 0.5],
        ],
    ],
    'head_adept_hood' => [
        'kind' => 'equipment', 'slot_type' => 'head', 'equip_class' => 'wizard', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 1.0],
        ],
    ],
    'shoulders_adept_mantle' => [
        'kind' => 'equipment', 'slot_type' => 'shoulders', 'equip_class' => 'wizard', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 1.5],
        ],
    ],
    'chest_adept_robe' => [
        'kind' => 'equipment', 'slot_type' => 'chest', 'equip_class' => 'wizard', 'rarity' => 2, 'required_level' => 3,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 1.0],
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 0.5],
        ],
    ],
    'wrists_adept_cuffs' => [
        'kind' => 'equipment', 'slot_type' => 'wrists', 'equip_class' => 'wizard', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 1.0],
        ],
    ],
    'hands_adept_gloves' => [
        'kind' => 'equipment', 'slot_type' => 'hands', 'equip_class' => 'wizard', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 1.0],
        ],
    ],
    'belt_adept_sash' => [
        'kind' => 'equipment', 'slot_type' => 'belt', 'equip_class' => 'wizard', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 1.0],
        ],
    ],
    'legs_adept_leggings' => [
        'kind' => 'equipment', 'slot_type' => 'legs', 'equip_class' => 'wizard', 'rarity' => 2, 'required_level' => 3,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 0.5],
        ],
    ],
    'boots_adept_slippers' => [
        'kind' => 'equipment', 'slot_type' => 'boots', 'equip_class' => 'wizard', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 1.5],
        ],
    ],
    'neck_traveler_amulet' => [
        'kind' => 'equipment', 'slot_type' => 'neck', 'equip_class' => 'any', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 0.5],
        ],
    ],
    'neck_oath_pendant' => [
        'kind' => 'equipment', 'slot_type' => 'neck', 'equip_class' => 'any', 'rarity' => 2, 'required_level' => 3,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 0.5],
        ],
    ],
    'cloak_traveler_cloak' => [
        'kind' => 'equipment', 'slot_type' => 'cloak', 'equip_class' => 'any', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 0.5],
        ],
    ],
    'cloak_shard_mantle' => [
        'kind' => 'equipment', 'slot_type' => 'cloak', 'equip_class' => 'any', 'rarity' => 2, 'required_level' => 3,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 1.0],
        ],
    ],
    'ring_copper_band' => [
        'kind' => 'equipment', 'slot_type' => 'ring', 'equip_class' => 'any', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 0.5],
        ],
    ],
    'ring_mana_signet' => [
        'kind' => 'equipment', 'slot_type' => 'ring', 'equip_class' => 'any', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 0.5],
        ],
    ],
    'ring_iron_seal' => [
        'kind' => 'equipment', 'slot_type' => 'ring', 'equip_class' => 'any', 'rarity' => 2, 'required_level' => 3,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 1.0],
        ],
    ],
];
