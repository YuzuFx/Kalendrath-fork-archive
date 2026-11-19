<?php

namespace OGame\Services\Items;

use Closure;
use Exception;
use Illuminate\Support\Facades\DB;
use OGame\Models\Enums\PlanetType;
use OGame\Models\FleetMission;
use OGame\Models\Planet;
use OGame\Models\Planet\Coordinate;
use OGame\Models\PveCamp;
use OGame\Services\Pve\PveCampService;

/**
 * Inventory rule for PvE content (decision of 2026-11-19): every PvE
 * departure that may bring an item back (expedition, attack on a PvE camp)
 * is refused server side while the kingdom's bag is at its capacity (32).
 *
 * PvE missions in flight count as places reserved at launch: a launch is
 * refused when bag items + PvE missions in flight >= capacity. Each PvE
 * mission gives one item at most (one expedition outcome, one roll per
 * attacking army), so loot alone never pushes the bag over the capacity.
 * The cap stays SOFT: an item won by a mission already gone is always given
 * (the "33rd place"), and taking an item off a hero, a swap or a discard are
 * always allowed; only new PvE departures wait. The bag can thus exceed 32
 * after items are taken off, never because of loot launched under the rule.
 *
 * Launches are serialised per kingdom: planets of the kingdom (ascending id),
 * then the kingdom lock (KingdomLock, lock order documented there), then the
 * upstream GameMission::start() inside the same transaction, so two
 * simultaneous launches cannot both take the last place.
 */
class PveLaunchGuard
{
    public const int EXPEDITION_MISSION = 15;

    /** @var list<int> */
    public const array ATTACK_MISSIONS = [1, 2];

    public function __construct(
        private readonly KingdomLock $lock,
        private readonly KingdomItemService $items,
        private readonly PveCampService $camps,
    ) {
    }

    /**
     * Runs a PvE launch ($start = the upstream GameMission::start()) under the
     * kingdom's planets lock then its kingdom lock, in one transaction. Must
     * be the outermost transaction for its counts to be current reads (the
     * fleet dispatch controllers are not in a transaction).
     *
     * @template T
     * @param Closure(): T $start
     * @return T
     */
    public function launch(int $userId, Closure $start): mixed
    {
        return DB::transaction(function () use ($userId, $start): mixed {
            Planet::where('user_id', $userId)->orderBy('id')->lockForUpdate()->pluck('id');
            $this->lock->acquire($userId);

            return $start();
        });
    }

    /**
     * True when an attack on these coordinates targets a PvE camp.
     */
    public function isCamp(Coordinate $target, PlanetType $targetType): bool
    {
        return $this->camps->campAt($target, $targetType) !== null;
    }

    /**
     * Refuses a PvE launch when bag items + PvE missions in flight reach the
     * capacity, with an explicit message ($context: expedition | raid).
     *
     * @throws Exception
     */
    public function assertCanLaunch(int $userId, string $context): void
    {
        $bag = $this->items->bagCount($userId);
        $inFlight = $this->inFlight($userId);
        $capacity = $this->items->bagCapacity();

        if ($bag + $inFlight >= $capacity) {
            throw new Exception(__('t_items.error.inventory_full_' . $context, [
                'used' => $bag,
                'reserved' => $inFlight,
                'capacity' => $capacity,
            ]));
        }
    }

    /**
     * PvE missions of a kingdom still on their way out (loot not rolled yet):
     * outbound expeditions and outbound attacks on a PvE camp, neither
     * processed nor recalled.
     */
    public function inFlight(int $userId): int
    {
        return FleetMission::where('user_id', $userId)
            ->where('processed', 0)
            ->where('canceled', 0)
            ->whereNull('parent_id')
            ->where(fn ($query) => $query->where('mission_type', self::EXPEDITION_MISSION)
                ->orWhere(fn ($attack) => $attack->whereIn('mission_type', self::ATTACK_MISSIONS)
                    ->whereIn('planet_id_to', PveCamp::query()->select('planet_id'))))
            ->count();
    }
}
