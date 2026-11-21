<?php

/*
 * Kalendrath items (hero equipment, kingdom inventory).
 * Names and descriptions of the alpha catalog are PROVISIONAL
 * (2026-11-21) and await Gregory’s validation.
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

    // Equipment classes: named after the archetypes (decided 2026-11-21).
    'equip_class' => [
        'war' => 'Warrior',
        'wizard' => 'Wizard',
        'any' => 'Common',
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
        '*' => 'Wood, Iron and Mana',
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

    'message' => [
        'item_found' => 'Item obtained: :item. It awaits you in the kingdom\'s inventory.',
    ],

    'error' => [
        'item_not_owned' => 'This item does not belong to your kingdom.',
        'item_not_available' => 'This item is not available (worn, reserved or destroyed).',
        'inventory_full_expedition' => 'Your kingdom\'s inventory is full (:used items in the bag and :reserved places held by your ongoing sorties, out of :capacity): discard items or wait for your sorties to end before sending a new expedition.',
        'inventory_full_raid' => 'Your kingdom\'s inventory is full (:used items in the bag and :reserved places held by your ongoing sorties, out of :capacity): discard items or wait for your sorties to end before attacking a camp.',
    ],

    'item' => [
        'head_warden_helm' => ['name' => 'Warden\'s Helm', 'description' => 'You run a finger over the marks left by years on watch.'],
        'shoulders_warden_pauldrons' => ['name' => 'Warden\'s Pauldrons', 'description' => 'You run a finger over the marks left by years on watch.'],
        'chest_warden_hauberk' => ['name' => 'Warden\'s Hauberk', 'description' => 'You run a finger over the marks left by years on watch.'],
        'wrists_warden_bracers' => ['name' => 'Warden\'s Bracers', 'description' => 'You run a finger over the marks left by years on watch.'],
        'hands_warden_gauntlets' => ['name' => 'Warden\'s Gauntlets', 'description' => 'You run a finger over the marks left by years on watch.'],
        'belt_warden_girdle' => ['name' => 'Warden\'s Girdle', 'description' => 'You run a finger over the marks left by years on watch.'],
        'legs_warden_greaves' => ['name' => 'Warden\'s Greaves', 'description' => 'You run a finger over the marks left by years on watch.'],
        'boots_warden_sabatons' => ['name' => 'Warden\'s Sabatons', 'description' => 'You run a finger over the marks left by years on watch.'],
        'head_adept_hood' => ['name' => 'Adept\'s Hood', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'shoulders_adept_mantle' => ['name' => 'Adept\'s Mantle', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'chest_adept_robe' => ['name' => 'Adept\'s Robe', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'wrists_adept_cuffs' => ['name' => 'Adept\'s Cuffs', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'hands_adept_gloves' => ['name' => 'Adept\'s Gloves', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'belt_adept_sash' => ['name' => 'Adept\'s Sash', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'legs_adept_leggings' => ['name' => 'Adept\'s Leggings', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'boots_adept_slippers' => ['name' => 'Adept\'s Slippers', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'neck_traveler_amulet' => ['name' => 'Traveler\'s Amulet', 'description' => 'You fasten the clasp. The road awaits.'],
        'neck_oath_pendant' => ['name' => 'Oath Pendant', 'description' => 'Beneath the dust you find traces of its first wearer.'],
        'cloak_traveler_cloak' => ['name' => 'Traveler\'s Cloak', 'description' => 'You fasten the clasp. The road awaits.'],
        'cloak_shard_mantle' => ['name' => 'Shard Mantle', 'description' => 'Beneath the dust you find traces of its first wearer.'],
        'ring_copper_band' => ['name' => 'Copper Band', 'description' => 'Beneath the dust you find traces of its first wearer.'],
        'ring_mana_signet' => ['name' => 'Mana Signet', 'description' => 'You hold the cloth to the light. Lines of Mana emerge in the weave.'],
        'ring_iron_seal' => ['name' => 'Iron Seal', 'description' => 'Beneath the dust you find traces of its first wearer.'],
        'chest_quilted_coat' => ['name' => 'Quilted Coat', 'description' => 'You feel every stitch in the rain. On the rampart, you are grateful for each one.'],
        'legs_road_trousers' => ['name' => 'Worksite Trousers', 'description' => 'You find dust from the Iron galleries in the folds.'],
        'head_rune_circlet' => ['name' => 'Rune Circlet', 'description' => 'You trace the grooves that draw in Mana.'],
        'neck_rune_pendant' => ['name' => 'Gallery Pendant', 'description' => 'You recognise the Dwarven marks that once pointed miners toward Iron seams.'],
        'shoulders_gate_pauldrons' => ['name' => 'Portcullis Pauldrons', 'description' => 'On the rampart, you hear the portcullis chains ringing through the Iron.'],
        'shoulders_ward_mantle' => ['name' => 'Mantle of Seals', 'description' => 'You count the seals stitched into the lining. The last can only be felt.'],
        'cloak_elfweave' => ['name' => 'Elvenweave Cloak', 'description' => 'You tilt the cloth and see the threads emerge beneath the patina.'],
        'chest_kaelhelm_mail' => ['name' => 'Hauberk of Kaelhelm', 'description' => 'You lift the mail from the rubble. Every link still bears a hammer mark.'],
        'chest_kaelhelm_vestment' => ['name' => 'Vestment of Kaelhelm', 'description' => 'You hold the vestment near a lamp. Its seams glow before the flame reaches them.'],
        'wrists_dwarven_cuffs' => ['name' => 'Dwarven Ruin Bracers', 'description' => 'You wipe away the soot and uncover an old workshop’s markings.'],
        'hands_watch_gloves' => ['name' => 'Watch Gloves', 'description' => 'You can keep hold of a banner in the rain. That alone puts them ahead of most gloves.'],
        'belt_woodward_belt' => ['name' => 'Sawmill Belt', 'description' => 'You rub a thumb over the buckle, polished by years of Wood dust.'],
        'legs_rampart_greaves' => ['name' => 'Rampart Legguards', 'description' => 'You brace against the stone. For all their weight, the plates move with you.'],
        'boots_ruin_boots' => ['name' => 'Ruin Boots', 'description' => 'You pry a weathered splinter of Wood from the sole.'],
        'ring_amber_loop' => ['name' => 'Amber Ring', 'description' => 'You see a sliver of Wood suspended in the amber.'],
        'head_rupture_helm' => ['name' => 'Helm of the Sundering', 'description' => 'You set the helm in place. A fissure runs through the Iron at eye level.'],
        'head_rupture_veil' => ['name' => 'Veil of the Sundering', 'description' => 'You unfold the veil. The dust falls and the Mana threads hang in the air.'],
        'cloak_threshold_cloak' => ['name' => 'Threshold Cloak', 'description' => 'You look away. The shadow at the edge of the cloth seems slow to follow.'],
        'wrists_obelisk_bracers' => ['name' => 'Obelisk Bracers', 'description' => 'In the engravings you recognise the outline of a Kaelhelm obelisk.'],
        'hands_oath_gauntlets' => ['name' => 'Gauntlets of the Broken Oath', 'description' => 'You spread your fingers. An old cut splits the mark engraved on the palm.'],
        'hands_straumar_gloves' => ['name' => 'Straumar Gloves', 'description' => 'You feel Mana pulse through the weave like a current beneath stone.'],
        'boots_deep_road_boots' => ['name' => 'Deep Road Boots', 'description' => 'You descend the Dwarven steps. The soles settle into the worn hollows.'],
        'ring_seam_ring' => ['name' => 'Rift Ring', 'description' => 'You glimpse a dark glimmer in the setting. It slips away when you look straight at it.'],
        'neck_straumar_torc' => ['name' => 'Straumar Torc', 'description' => 'You feel Mana well up in time with the Straumar, even in the stillness of stone.'],
    ],
];
