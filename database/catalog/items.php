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
| ALPHA CATALOG (2026-11-21): two equipment
| classes (war = Warrior, wizard = Sorcerer) plus "any" jewels and cloaks,
| budget and progression documented in the 2026-11-21 loot report.
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
    'chest_quilted_coat' => [
        'kind' => 'equipment', 'slot_type' => 'chest', 'equip_class' => 'any', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 1.0],
        ],
    ],
    'legs_road_trousers' => [
        'kind' => 'equipment', 'slot_type' => 'legs', 'equip_class' => 'any', 'rarity' => 1, 'required_level' => 1,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 0.5],
        ],
    ],
    'head_rune_circlet' => [
        'kind' => 'equipment', 'slot_type' => 'head', 'equip_class' => 'any', 'rarity' => 2, 'required_level' => 5,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 1.5],
        ],
    ],
    'neck_rune_pendant' => [
        'kind' => 'equipment', 'slot_type' => 'neck', 'equip_class' => 'any', 'rarity' => 3, 'required_level' => 15,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 2.0],
        ],
    ],
    'shoulders_gate_pauldrons' => [
        'kind' => 'equipment', 'slot_type' => 'shoulders', 'equip_class' => 'war', 'rarity' => 3, 'required_level' => 10,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 4.0],
        ],
    ],
    'shoulders_ward_mantle' => [
        'kind' => 'equipment', 'slot_type' => 'shoulders', 'equip_class' => 'wizard', 'rarity' => 3, 'required_level' => 10,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 4.0],
        ],
    ],
    'cloak_elfweave' => [
        'kind' => 'equipment', 'slot_type' => 'cloak', 'equip_class' => 'any', 'rarity' => 3, 'required_level' => 15,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 1.0],
        ],
    ],
    'chest_kaelhelm_mail' => [
        'kind' => 'equipment', 'slot_type' => 'chest', 'equip_class' => 'war', 'rarity' => 5, 'required_level' => 30,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 4.0],
        ],
    ],
    'chest_kaelhelm_vestment' => [
        'kind' => 'equipment', 'slot_type' => 'chest', 'equip_class' => 'wizard', 'rarity' => 5, 'required_level' => 30,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 3.0],
        ],
    ],
    'wrists_dwarven_cuffs' => [
        'kind' => 'equipment', 'slot_type' => 'wrists', 'equip_class' => 'any', 'rarity' => 2, 'required_level' => 5,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'crystal', 'value' => 1.5],
        ],
    ],
    'hands_watch_gloves' => [
        'kind' => 'equipment', 'slot_type' => 'hands', 'equip_class' => 'any', 'rarity' => 2, 'required_level' => 5,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 2.0],
        ],
    ],
    'belt_woodward_belt' => [
        'kind' => 'equipment', 'slot_type' => 'belt', 'equip_class' => 'any', 'rarity' => 3, 'required_level' => 15,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 2.0],
        ],
    ],
    'legs_rampart_greaves' => [
        'kind' => 'equipment', 'slot_type' => 'legs', 'equip_class' => 'any', 'rarity' => 3, 'required_level' => 15,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 4.0],
        ],
    ],
    'boots_ruin_boots' => [
        'kind' => 'equipment', 'slot_type' => 'boots', 'equip_class' => 'any', 'rarity' => 2, 'required_level' => 5,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 1.5],
        ],
    ],
    'ring_amber_loop' => [
        'kind' => 'equipment', 'slot_type' => 'ring', 'equip_class' => 'any', 'rarity' => 3, 'required_level' => 10,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 2.0],
        ],
    ],
    'head_rupture_helm' => [
        'kind' => 'equipment', 'slot_type' => 'head', 'equip_class' => 'war', 'rarity' => 4, 'required_level' => 20,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 3.0],
        ],
    ],
    'head_rupture_veil' => [
        'kind' => 'equipment', 'slot_type' => 'head', 'equip_class' => 'wizard', 'rarity' => 4, 'required_level' => 20,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 2.5],
        ],
    ],
    'cloak_threshold_cloak' => [
        'kind' => 'equipment', 'slot_type' => 'cloak', 'equip_class' => 'any', 'rarity' => 4, 'required_level' => 25,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 4.0],
        ],
    ],
    'wrists_obelisk_bracers' => [
        'kind' => 'equipment', 'slot_type' => 'wrists', 'equip_class' => 'any', 'rarity' => 3, 'required_level' => 20,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 2.0],
        ],
    ],
    'hands_oath_gauntlets' => [
        'kind' => 'equipment', 'slot_type' => 'hands', 'equip_class' => 'war', 'rarity' => 4, 'required_level' => 25,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 3.5],
        ],
    ],
    'hands_straumar_gloves' => [
        'kind' => 'equipment', 'slot_type' => 'hands', 'equip_class' => 'wizard', 'rarity' => 4, 'required_level' => 25,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 2.5],
        ],
    ],
    'boots_deep_road_boots' => [
        'kind' => 'equipment', 'slot_type' => 'boots', 'equip_class' => 'any', 'rarity' => 3, 'required_level' => 20,
        'effects' => [
            ['type' => 'troop_combat_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 3.0],
        ],
    ],
    'ring_seam_ring' => [
        'kind' => 'equipment', 'slot_type' => 'ring', 'equip_class' => 'any', 'rarity' => 4, 'required_level' => 25,
        'effects' => [
            ['type' => 'garrison_defense_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 3.0],
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'deuterium', 'value' => 1.0],
        ],
    ],
    'neck_straumar_torc' => [
        'kind' => 'equipment', 'slot_type' => 'neck', 'equip_class' => 'any', 'rarity' => 5, 'required_level' => 30,
        'effects' => [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => '*', 'value' => 1.5],
        ],
    ],
];
