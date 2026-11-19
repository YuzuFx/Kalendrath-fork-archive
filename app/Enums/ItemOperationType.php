<?php

namespace OGame\Enums;

/**
 * Type of an item operation (header of the item journal).
 */
enum ItemOperationType: string
{
    case LOOT_GRANT = 'loot_grant';
    case EQUIP = 'equip';
    case UNEQUIP = 'unequip';
    case SWAP = 'swap';
    case ADMIN_GRANT = 'admin_grant';
    case ADMIN_REVOKE = 'admin_revoke';
    case MIGRATION_IMPORT = 'migration_import';
    // The player throws an item away (state destroyed, logical deletion).
    case DISCARD = 'discard';
    // A catalog definition changed (buff or nerf of one item); no movement,
    // subject_user_id = 0 (system), old and new effects in meta.
    case DEFINITION_REBALANCE = 'definition_rebalance';
}
