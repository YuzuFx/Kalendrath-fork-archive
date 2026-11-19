<?php

namespace OGame\Enums;

/**
 * Closed state machine of a kingdom item (note U3). The alpha uses
 * AVAILABLE, EQUIPPED and DESTROYED (discarded by the player, logical
 * deletion); RESERVED, IN_TRANSIT and CONSUMED are reserved for the market,
 * convoys and crafting. Any action is allowed only from AVAILABLE.
 */
enum KingdomItemState: string
{
    case AVAILABLE = 'available';
    case EQUIPPED = 'equipped';
    case RESERVED = 'reserved';
    case IN_TRANSIT = 'in_transit';
    case CONSUMED = 'consumed';
    case DESTROYED = 'destroyed';
}
