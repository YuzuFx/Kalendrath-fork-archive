<?php

/*
 * Objets de Kalendrath (équipement du héros, inventaire du royaume).
 * Les noms et descriptions des objets du catalogue alpha sont
 * PROVISOIRES (2026-11-21) : à valider par Gregory, descriptions incluses.
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

    // Classes d'équipement : nommées d'après les archétypes (décidé le 2026-11-21).
    'equip_class' => [
        'war' => 'Guerrier',
        'wizard' => 'Sorcier',
        'any' => 'Commun',
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
        '*' => 'Bois, Fer et Mana',
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

    'message' => [
        'item_found' => 'Objet obtenu : :item. Il vous attend dans l\'inventaire du royaume.',
    ],

    'error' => [
        'item_not_owned' => 'Cet objet n\'appartient pas à votre royaume.',
        'item_not_available' => 'Cet objet n\'est pas disponible (porté, réservé ou détruit).',
        'inventory_full_expedition' => 'L\'inventaire de votre royaume est plein (:used objets au sac et :reserved places réservées par vos sorties en cours, sur :capacity) : supprimez des objets ou attendez le retour de vos sorties avant d\'envoyer une nouvelle expédition.',
        'inventory_full_raid' => 'L\'inventaire de votre royaume est plein (:used objets au sac et :reserved places réservées par vos sorties en cours, sur :capacity) : supprimez des objets ou attendez le retour de vos sorties avant d\'attaquer un camp.',
    ],

    'item' => [
        'head_warden_helm' => ['name' => 'Heaume du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'shoulders_warden_pauldrons' => ['name' => 'Spalières du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'chest_warden_hauberk' => ['name' => 'Haubert du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'wrists_warden_bracers' => ['name' => 'Brassards du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'hands_warden_gauntlets' => ['name' => 'Gantelets du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'belt_warden_girdle' => ['name' => 'Ceinturon du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'legs_warden_greaves' => ['name' => 'Grèves du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'boots_warden_sabatons' => ['name' => 'Solerets du guetteur', 'description' => 'Vous suivez du doigt les marques laissées par des années de veille.'],
        'head_adept_hood' => ['name' => 'Capuche de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'shoulders_adept_mantle' => ['name' => 'Mantelet de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'chest_adept_robe' => ['name' => 'Robe de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'wrists_adept_cuffs' => ['name' => 'Manchettes de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'hands_adept_gloves' => ['name' => 'Gants de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'belt_adept_sash' => ['name' => 'Écharpe de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'legs_adept_leggings' => ['name' => 'Chausses de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'boots_adept_slippers' => ['name' => 'Sandales de l\'adepte', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'neck_traveler_amulet' => ['name' => 'Amulette du voyageur', 'description' => 'Vous refermez l’attache. Le voyage peut reprendre.'],
        'neck_oath_pendant' => ['name' => 'Pendentif du Serment', 'description' => 'Vous retrouvez sous la poussière les traces de son premier porteur.'],
        'cloak_traveler_cloak' => ['name' => 'Cape du voyageur', 'description' => 'Vous refermez l’attache. Le voyage peut reprendre.'],
        'cloak_shard_mantle' => ['name' => 'Manteau des Éclats', 'description' => 'Vous retrouvez sous la poussière les traces de son premier porteur.'],
        'ring_copper_band' => ['name' => 'Anneau de cuivre', 'description' => 'Vous retrouvez sous la poussière les traces de son premier porteur.'],
        'ring_mana_signet' => ['name' => 'Chevalière de mana', 'description' => 'Vous tendez l’étoffe à la lumière. La trame dessine des lignes de Mana.'],
        'ring_iron_seal' => ['name' => 'Sceau de fer', 'description' => 'Vous retrouvez sous la poussière les traces de son premier porteur.'],
        'chest_quilted_coat' => ['name' => 'Gambison matelassé', 'description' => 'Vous sentez chaque couture sous la pluie. Au rempart, vous les remerciez toutes.'],
        'legs_road_trousers' => ['name' => 'Braies de chantier', 'description' => 'Vous retrouvez dans les plis la poussière des galeries de Fer.'],
        'head_rune_circlet' => ['name' => 'Diadème runique', 'description' => 'Vous suivez du doigt les rainures qui recueillent le Mana.'],
        'neck_rune_pendant' => ['name' => 'Pendentif des galeries', 'description' => 'Vous reconnaissez les marques naines qui indiquaient les filons de Fer.'],
        'shoulders_gate_pauldrons' => ['name' => 'Spalières de la herse', 'description' => 'Du haut du rempart, vous entendez les chaînes de la herse vibrer dans le Fer.'],
        'shoulders_ward_mantle' => ['name' => 'Mantelet des sceaux', 'description' => 'Vous comptez les sceaux cousus dans la doublure. Le dernier se devine au toucher.'],
        'cloak_elfweave' => ['name' => 'Cape de trame elfique', 'description' => 'Vous inclinez l’étoffe et voyez apparaître les fils sous leur patine.'],
        'chest_kaelhelm_mail' => ['name' => 'Haubert de Kaelhelm', 'description' => 'Vous soulevez la maille prise dans les décombres. Chaque anneau porte encore la marque du marteau.'],
        'chest_kaelhelm_vestment' => ['name' => 'Habit de Kaelhelm', 'description' => 'Vous approchez l’habit d’une lampe. Les coutures brillent avant que la flamme les éclaire.'],
        'wrists_dwarven_cuffs' => ['name' => 'Brassards des vestiges nains', 'description' => 'Vous essuyez la suie et retrouvez les repères d’un ancien atelier.'],
        'hands_watch_gloves' => ['name' => 'Gants de veille', 'description' => 'Vous pouvez tenir une bannière sous la pluie. C’est déjà une victoire sur les gants ordinaires.'],
        'belt_woodward_belt' => ['name' => 'Ceinture des scieries', 'description' => 'Vous passez le pouce sur la boucle polie par des années de poussière de Bois.'],
        'legs_rampart_greaves' => ['name' => 'Jambières de rempart', 'description' => 'Vous prenez appui sur la pierre. Les plaques suivent votre pas malgré leur poids.'],
        'boots_ruin_boots' => ['name' => 'Bottes des ruines', 'description' => 'Vous délogez de la semelle une écharde de Bois durcie par le temps.'],
        'ring_amber_loop' => ['name' => 'Anneau d’ambre', 'description' => 'Vous voyez un éclat de Bois prisonnier de l’ambre.'],
        'head_rupture_helm' => ['name' => 'Heaume de la Rupture', 'description' => 'Vous ajustez le heaume. Une fente traverse le Fer à hauteur des yeux.'],
        'head_rupture_veil' => ['name' => 'Voile de la Rupture', 'description' => 'Vous dépliez le voile. La poussière tombe et les fils de Mana restent en suspens.'],
        'cloak_threshold_cloak' => ['name' => 'Cape du seuil', 'description' => 'Vous détournez les yeux. L’ombre au bord de l’étoffe semble tarder à vous suivre.'],
        'wrists_obelisk_bracers' => ['name' => 'Brassards d’obélisque', 'description' => 'Vous reconnaissez dans les gravures la silhouette d’un obélisque de Kaelhelm.'],
        'hands_oath_gauntlets' => ['name' => 'Gantelets du Serment Rompu', 'description' => 'Vous écartez les doigts. Une ancienne entaille coupe en deux le signe gravé sur la paume.'],
        'hands_straumar_gloves' => ['name' => 'Gants des Straumar', 'description' => 'Vous sentez le Mana pulser à travers la trame, comme un courant sous la pierre.'],
        'boots_deep_road_boots' => ['name' => 'Bottes des voies profondes', 'description' => 'Vous descendez les marches naines. Les semelles épousent les creux de la pierre.'],
        'ring_seam_ring' => ['name' => 'Anneau de la faille', 'description' => 'Vous apercevez dans le sertissage une lueur sombre qui se dérobe au regard.'],
        'neck_straumar_torc' => ['name' => 'Torque des Straumar', 'description' => 'Vous sentez le Mana affluer au rythme des Straumar, jusque dans le silence de la pierre.'],
    ],
];
