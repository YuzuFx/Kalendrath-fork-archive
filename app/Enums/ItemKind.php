<?php

namespace OGame\Enums;

/**
 * What an item definition is. Only EQUIPMENT is used in the alpha; the other
 * kinds are reserved for components, consumables, patterns, quest items and
 * cosmetics (planned for 2027, no table or logic yet).
 */
enum ItemKind: string
{
    case EQUIPMENT = 'equipment';
    case COMPONENT = 'component';
    case CONSUMABLE = 'consumable';
    case PATTERN = 'pattern';
    case QUEST = 'quest';
    case COSMETIC = 'cosmetic';
}
