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
| ALL VALUES ARE PROVISIONAL: garrison, buildings and starting stock are to be
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

    'retaliation' => [
        'enabled' => env('PVE_RETALIATION_ENABLED', true),
        'preparation_hours' => 8,
        'max_travel_minutes' => 120,
        'max_processing_delay_minutes' => 15,
        'camp_cooldown_hours' => 12,
        'target_attempts_24h' => 1,
        'target_attempts_168h' => 2,
        'min_account_age_hours' => 72,
        'min_city_age_hours' => 24,
        'min_defense_wood_equivalent' => 60000,
        'max_combat_axis_ratio' => 0.60,
        'max_detachment_share' => 0.25,
        'min_garrison_reserve_share' => 0.50,
        'max_fuel_stock_share' => 0.10,
        'max_loot_stock_share' => 0.10,
        'protected_production_hours' => 6,
        'max_loot_production_hours_per_hit' => 2,
        'max_loot_production_hours_24h' => 2,
        'max_loot_production_hours_168h' => 4,
        'max_unit_loss_share_per_hit' => 0.10,
        'max_unit_loss_share_24h' => 0.10,
        'max_unit_loss_share_168h' => 0.20,
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
            // A camp produces like a player (decision of 2026-11-21): these
            // buildings give it its production (and its storage ceiling), the
            // upstream formulas do the rest, including the economy speed.
            // PROVISIONAL levels, to calibrate with the simulator.
            'buildings' => [
                'metal_mine' => 10,
                'crystal_mine' => 8,
                'deuterium_synthesizer' => 6,
                'solar_plant' => 12,
                'metal_store' => 3,
                'crystal_store' => 3,
                'deuterium_store' => 2,
            ],
            // Stock at creation (metal = Wood, crystal = Iron, deuterium = Mana).
            // After that the camp only has what it produces and what it keeps.
            'start_resources' => ['metal' => 20000, 'crystal' => 10000, 'deuterium' => 5000],
        ],
    ],
];
