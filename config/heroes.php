<?php

/*
|--------------------------------------------------------------------------
| Hero system tuning (GDD section 5.5)
|--------------------------------------------------------------------------
|
| Every value here is a tuning knob, not final balance (Phase 6). Values
| marked TBD are still open in the GDD.
*/
return [
    'advisors' => [
        // Seats of the hero's Council. Seats will later be unlocked by
        // Imperial Prestige tiers (GDD 5.6, not built yet): until then all open.
        'max_count' => 4,

        // Advisor level cap. GDD: 10 to 15, final value TBD.
        'max_level' => 10,

        // Ceiling of the SUM of the advisors' bonus on one axis, as a ratio of
        // the hero's own ceiling on that axis. GDD: one third to one half, TBD.
        'axis_cap_ratio' => 0.5,

        // Equipment positions available to an advisor ("lighter inventory",
        // detail TBD in the GDD): all twelve until decided (2026-11-19).
        'slots' => [
            'head', 'neck', 'shoulders', 'cloak', 'chest', 'wrists', 'hands', 'belt', 'legs', 'boots', 'ring_1', 'ring_2',
        ],
    ],

    /*
     * Equipment classes each archetype may wear (EquipClass). Adding an
     * archetype is one line here, not a review of the item catalog. An
     * archetype missing from this list may only wear "any" items.
     * Provisional values (classes to be validated by Gregory).
     */
    'archetypes' => [
        'warrior' => ['equip_classes' => ['war', 'any']],
        'sorcerer' => ['equip_classes' => ['wizard', 'any']],
    ],

    // Archetypes of the alpha: a kingdom without a main hero draws loot from
    // the union of their classes.
    'alpha_archetypes' => ['warrior', 'sorcerer'],

    /*
     * Optional ceiling of the total bonus (talents + equipment) on an effect
     * axis, keyed by effect_type. EMPTY BY DEFAULT (decision of 2026-11-21):
     * the hero is a central mechanic and its weight comes from the talent
     * trees (budget of 29 points, GDD 5.5), not from a global ceiling. An
     * effect_type absent from this list is not capped; add a line to cap one
     * axis if the simulation shows a runaway. The advisors' ratio
     * (advisors.axis_cap_ratio) only applies to an axis that has a ceiling.
     */
    'axis_caps' => [
        // 'production_bonus_percent' => 40,
    ],
];
