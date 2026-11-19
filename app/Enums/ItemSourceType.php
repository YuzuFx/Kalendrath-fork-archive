<?php

namespace OGame\Enums;

/**
 * Where an item was born (provenance, written once at creation and never
 * rebuilt). Alpha sources: EXPEDITION, PVE_RAID (lot 2) and ADMIN (tests,
 * demo); the others are reserved.
 */
enum ItemSourceType: string
{
    case EXPEDITION = 'expedition';
    case PVE_RAID = 'pve_raid';
    case CRAFT = 'craft';
    case QUEST = 'quest';
    case EVENT = 'event';
    case ADMIN = 'admin';
    case MIGRATION = 'migration';
    case MARKET = 'market';
    case NPC_SHOP = 'npc_shop';
}
