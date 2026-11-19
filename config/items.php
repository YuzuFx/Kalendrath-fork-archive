<?php

/*
|--------------------------------------------------------------------------
| Kingdom items (inventory) tuning
|--------------------------------------------------------------------------
|
| Decisions of 2026-11-19 (Gregory). Every value is a tuning knob.
*/
return [
    'inventory' => [
        // Bag places of a kingdom. Equipped items do not count. The cap is
        // "soft" for loot: an item won on an expedition is never lost, it may
        // push the bag over the cap; launching a new expedition is then
        // refused until the player makes room (decision to be confirmed).
        'capacity' => (int) env('ITEMS_INVENTORY_CAPACITY', 32),
    ],
];
