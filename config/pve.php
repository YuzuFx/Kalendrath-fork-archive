<?php

/*
|--------------------------------------------------------------------------
| PvE bot camps (GDD 5.8A, alpha lot 2)
|--------------------------------------------------------------------------
|
| A camp is a planet of the PvE bot account: attacked with the upstream
| attack mission and battle engines, it does not depend on any war system
| nor on the War Mode. A victorious raid rolls item loot per attacking army
| (config/loot.php, table "loot_table").
|
| ALL VALUES ARE PROVISIONAL: garrison, resources and respawn delay are to be
| calibrated for a x5 server (see the lot 2 report). Displayed names are
| [TBD] (resources/lang/{fr,en}/t_pve.php): never invent a bot name.
*/
return [
    // The single technical account that owns every camp. Its username is
    // shown in the galaxy view: [TBD] until Gregory names the bots.
    'bot_account' => [
        'username' => env('PVE_BOT_USERNAME', '[TBD] PvE'),
        'email' => 'pve-bot@kalendrath.invalid',
        // Combat research of the camps (Whetstone, Arcane Infusion, Armorsmithing).
        'research' => [
            'weapon_technology' => 0,
            'shielding_technology' => 0,
            'armor_technology' => 0,
        ],
    ],

    // Positions where camps are spawned when no coordinate is given: outside
    // the 4-12 range the allocator gives to new players.
    'spawn_positions' => [1, 2, 3, 13, 14, 15],

    // One camp type for the alpha (no new type without necessity).
    'camp_types' => [
        'hostile_camp' => [
            'tier' => 1,
            'loot_table' => 'pve_camp.t1',
            // Machine names (Soldier, Guard; rocket_launcher: defense, name [TBD]).
            'garrison' => [
                'light_fighter' => 20,
                'heavy_fighter' => 5,
                'rocket_launcher' => 10,
            ],
            // Lootable stock at each restock: metal = Wood, crystal = Iron, deuterium = Mana.
            'resources' => ['metal' => 20000, 'crystal' => 10000, 'deuterium' => 5000],
            // Game hours before the garrison comes back after a battle,
            // divided by the economy speed of the server (x5: 1 h 12 min).
            'respawn_hours' => 6,
        ],
    ],
];
