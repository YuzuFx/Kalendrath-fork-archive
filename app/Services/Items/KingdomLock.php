<?php

namespace OGame\Services\Items;

use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Stable lock of one kingdom's inventory: a row of kingdom_locks taken FOR
 * UPDATE, held until the surrounding transaction commits. It serialises
 * every path that changes what a kingdom holds or reserves: PvE launch
 * (PveLaunchGuard), loot grant (LootService), discard, equip, unequip,
 * swap (HeroEquipmentService), purge (KingdomPurgeService). It is taken even
 * when the inventory is empty (the row is created on first use).
 *
 * GLOBAL LOCK ORDER (never acquire an earlier level while holding a later one):
 *
 *   1. planets of the kingdom, by ascending id   (updateFleetMissions, HeroEffectsChanged, PveLaunchGuard)
 *   2. fleet missions                             (updateFleetMissions)
 *   3. kingdom lock (this row)
 *   4. heroes (main hero, advisor)
 *   5. kingdom_items, by ascending id
 *
 * A level may be skipped (discard takes 3 then 5), never climbed back. A
 * battle resolved under the attacker's planets may take the kingdom lock of
 * another participant of a union (3 without its 1): it then never asks for
 * that kingdom's planets. Several kingdom locks in one transaction are taken
 * by ascending user id (acquireMany).
 *
 * A dedicated row rather than the users row: upstream locks users in other
 * orders (PlayerService::update locks users then writes planets, like the
 * HalvingService), which would invert levels 1 and 3.
 */
class KingdomLock
{
    public function acquire(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        if (DB::transactionLevel() === 0) {
            throw new LogicException('KingdomLock::acquire() must run inside a transaction.');
        }

        if ($this->lockRow($userId) !== null) {
            return;
        }

        // First use of this kingdom: create the row, then lock it. A
        // concurrent first use is absorbed by insertOrIgnore.
        DB::table('kingdom_locks')->insertOrIgnore(['user_id' => $userId]);
        if ($this->lockRow($userId) === null) {
            throw new LogicException('Could not acquire the kingdom lock of user ' . $userId . '.');
        }
    }

    /**
     * @param list<int> $userIds
     */
    public function acquireMany(array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter($userIds, fn (int $id) => $id > 0)));
        sort($userIds);
        foreach ($userIds as $userId) {
            $this->acquire($userId);
        }
    }

    /**
     * Locking (current) read of the kingdom's row; NULL when it does not exist yet.
     *
     * @phpstan-impure
     */
    private function lockRow(int $userId): mixed
    {
        return DB::table('kingdom_locks')->where('user_id', $userId)->lockForUpdate()->value('user_id');
    }
}
