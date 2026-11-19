<?php

/*
 * Kalendrath items (hero equipment, kingdom inventory).
 * Names and descriptions of the starter catalog are PROVISIONAL
 * (2026-11-19): to be validated, descriptions [TBD].
 * Keys derive from the machine code: t_items.item.<code>.name / .description.
 */
return [
    'slot_type' => [
        'head' => 'Head',
        'neck' => 'Neck',
        'shoulders' => 'Shoulders',
        'cloak' => 'Cloak',
        'chest' => 'Chest',
        'wrists' => 'Wrists',
        'hands' => 'Hands',
        'belt' => 'Belt',
        'legs' => 'Legs',
        'boots' => 'Feet',
        'ring' => 'Ring',
    ],

    // Equipment classes: provisional names.
    'equip_class' => [
        'heavy' => 'Heavy (Warrior)',
        'arcane' => 'Arcane (Sorcerer)',
        'any' => 'Any',
    ],

    'rarity' => [
        '1' => 'White',
        '2' => 'Green',
        '3' => 'Blue',
        '4' => 'Purple',
        '5' => 'Orange',
    ],

    'state' => [
        'available' => 'In the bag',
        'equipped' => 'Worn',
        'reserved' => 'Reserved',
        'in_transit' => 'On the way',
        'consumed' => 'Consumed',
        'destroyed' => 'Destroyed',
    ],

    'target' => [
        '*' => 'all resources',
        'metal' => 'Wood',
        'crystal' => 'Iron',
        'deuterium' => 'Mana',
    ],

    'effect' => [
        'production_bonus_percent' => '+:value% production (:target)',
        'troop_combat_bonus_percent' => '+:value% troop strength',
        'garrison_defense_bonus_percent' => '+:value% garrison defence',
        'generic' => ':type: +:value',
    ],

    'inventory' => [
        'places' => ':used / :capacity places',
    ],

    'error' => [
        'item_not_owned' => 'This item does not belong to your kingdom.',
        'item_not_available' => 'This item is not available (worn, reserved or destroyed).',
        'inventory_full_expedition' => 'Your kingdom\'s inventory is full (:used items in the bag and :reserved places held by your ongoing sorties, out of :capacity): discard items or wait for your sorties to end before sending a new expedition.',
        'inventory_full_raid' => 'Your kingdom\'s inventory is full (:used items in the bag and :reserved places held by your ongoing sorties, out of :capacity): discard items or wait for your sorties to end before attacking a camp.',
    ],

    'item' => [
        'head_warden_helm' => ['name' => 'Warden\'s Helm (provisional)', 'description' => '[TBD]'],
        'shoulders_warden_pauldrons' => ['name' => 'Warden\'s Pauldrons (provisional)', 'description' => '[TBD]'],
        'chest_warden_hauberk' => ['name' => 'Warden\'s Hauberk (provisional)', 'description' => '[TBD]'],
        'wrists_warden_bracers' => ['name' => 'Warden\'s Bracers (provisional)', 'description' => '[TBD]'],
        'hands_warden_gauntlets' => ['name' => 'Warden\'s Gauntlets (provisional)', 'description' => '[TBD]'],
        'belt_warden_girdle' => ['name' => 'Warden\'s Girdle (provisional)', 'description' => '[TBD]'],
        'legs_warden_greaves' => ['name' => 'Warden\'s Greaves (provisional)', 'description' => '[TBD]'],
        'boots_warden_sabatons' => ['name' => 'Warden\'s Sabatons (provisional)', 'description' => '[TBD]'],
        'head_adept_hood' => ['name' => 'Adept\'s Hood (provisional)', 'description' => '[TBD]'],
        'shoulders_adept_mantle' => ['name' => 'Adept\'s Mantle (provisional)', 'description' => '[TBD]'],
        'chest_adept_robe' => ['name' => 'Adept\'s Robe (provisional)', 'description' => '[TBD]'],
        'wrists_adept_cuffs' => ['name' => 'Adept\'s Cuffs (provisional)', 'description' => '[TBD]'],
        'hands_adept_gloves' => ['name' => 'Adept\'s Gloves (provisional)', 'description' => '[TBD]'],
        'belt_adept_sash' => ['name' => 'Adept\'s Sash (provisional)', 'description' => '[TBD]'],
        'legs_adept_leggings' => ['name' => 'Adept\'s Leggings (provisional)', 'description' => '[TBD]'],
        'boots_adept_slippers' => ['name' => 'Adept\'s Slippers (provisional)', 'description' => '[TBD]'],
        'neck_traveler_amulet' => ['name' => 'Traveler\'s Amulet (provisional)', 'description' => '[TBD]'],
        'neck_oath_pendant' => ['name' => 'Oath Pendant (provisional)', 'description' => '[TBD]'],
        'cloak_traveler_cloak' => ['name' => 'Traveler\'s Cloak (provisional)', 'description' => '[TBD]'],
        'cloak_shard_mantle' => ['name' => 'Shard Mantle (provisional)', 'description' => '[TBD]'],
        'ring_copper_band' => ['name' => 'Copper Band (provisional)', 'description' => '[TBD]'],
        'ring_mana_signet' => ['name' => 'Mana Signet (provisional)', 'description' => '[TBD]'],
        'ring_iron_seal' => ['name' => 'Iron Seal (provisional)', 'description' => '[TBD]'],
    ],
];
