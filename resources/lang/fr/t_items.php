<?php

/*
 * Objets de Kalendrath (équipement du héros, inventaire du royaume).
 * Les noms et descriptions des objets du catalogue de départ sont
 * PROVISOIRES (2026-11-19) : à valider par Gregory, descriptions [TBD].
 * Clés dérivées du code machine : t_items.item.<code>.name / .description.
 */
return [
    'slot_type' => [
        'head' => 'Tête',
        'neck' => 'Collier',
        'shoulders' => 'Épaules',
        'cloak' => 'Cape',
        'chest' => 'Torse',
        'wrists' => 'Poignets',
        'hands' => 'Mains',
        'belt' => 'Ceinture',
        'legs' => 'Jambes',
        'boots' => 'Pieds',
        'ring' => 'Anneau',
    ],

    // Classes d'équipement : noms provisoires.
    'equip_class' => [
        'heavy' => 'Lourd (Guerrier)',
        'arcane' => 'Arcanique (Sorcier)',
        'any' => 'Tous',
    ],

    'rarity' => [
        '1' => 'Blanc',
        '2' => 'Vert',
        '3' => 'Bleu',
        '4' => 'Violet',
        '5' => 'Orange',
    ],

    'state' => [
        'available' => 'Au sac',
        'equipped' => 'Porté',
        'reserved' => 'Réservé',
        'in_transit' => 'En route',
        'consumed' => 'Consommé',
        'destroyed' => 'Détruit',
    ],

    'target' => [
        '*' => 'toutes les ressources',
        'metal' => 'Bois',
        'crystal' => 'Fer',
        'deuterium' => 'Mana',
    ],

    'effect' => [
        'production_bonus_percent' => '+:value % de production (:target)',
        'troop_combat_bonus_percent' => '+:value % de puissance des troupes',
        'garrison_defense_bonus_percent' => '+:value % de défense de la garnison',
        'generic' => ':type : +:value',
    ],

    'inventory' => [
        'places' => ':used / :capacity places',
    ],

    'error' => [
        'item_not_owned' => 'Cet objet n\'appartient pas à votre royaume.',
        'item_not_available' => 'Cet objet n\'est pas disponible (porté, réservé ou détruit).',
        'inventory_full_expedition' => 'L\'inventaire de votre royaume est plein : libérez des places avant d\'envoyer une nouvelle expédition.',
    ],

    'item' => [
        'head_warden_helm' => ['name' => 'Heaume du guetteur (provisoire)', 'description' => '[TBD]'],
        'shoulders_warden_pauldrons' => ['name' => 'Spalières du guetteur (provisoire)', 'description' => '[TBD]'],
        'chest_warden_hauberk' => ['name' => 'Haubert du guetteur (provisoire)', 'description' => '[TBD]'],
        'wrists_warden_bracers' => ['name' => 'Brassards du guetteur (provisoire)', 'description' => '[TBD]'],
        'hands_warden_gauntlets' => ['name' => 'Gantelets du guetteur (provisoire)', 'description' => '[TBD]'],
        'belt_warden_girdle' => ['name' => 'Ceinturon du guetteur (provisoire)', 'description' => '[TBD]'],
        'legs_warden_greaves' => ['name' => 'Grèves du guetteur (provisoire)', 'description' => '[TBD]'],
        'boots_warden_sabatons' => ['name' => 'Solerets du guetteur (provisoire)', 'description' => '[TBD]'],
        'head_adept_hood' => ['name' => 'Capuche de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'shoulders_adept_mantle' => ['name' => 'Mantelet de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'chest_adept_robe' => ['name' => 'Robe de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'wrists_adept_cuffs' => ['name' => 'Manchettes de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'hands_adept_gloves' => ['name' => 'Gants de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'belt_adept_sash' => ['name' => 'Écharpe de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'legs_adept_leggings' => ['name' => 'Chausses de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'boots_adept_slippers' => ['name' => 'Sandales de l\'adepte (provisoire)', 'description' => '[TBD]'],
        'neck_traveler_amulet' => ['name' => 'Amulette du voyageur (provisoire)', 'description' => '[TBD]'],
        'neck_oath_pendant' => ['name' => 'Pendentif du Serment (provisoire)', 'description' => '[TBD]'],
        'cloak_traveler_cloak' => ['name' => 'Cape du voyageur (provisoire)', 'description' => '[TBD]'],
        'cloak_shard_mantle' => ['name' => 'Manteau des Éclats (provisoire)', 'description' => '[TBD]'],
        'ring_copper_band' => ['name' => 'Anneau de cuivre (provisoire)', 'description' => '[TBD]'],
        'ring_mana_signet' => ['name' => 'Chevalière de mana (provisoire)', 'description' => '[TBD]'],
        'ring_iron_seal' => ['name' => 'Sceau de fer (provisoire)', 'description' => '[TBD]'],
    ],
];
