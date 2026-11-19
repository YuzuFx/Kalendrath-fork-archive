<?php

namespace OGame\Services\Hero;

use Closure;
use Illuminate\Support\Facades\DB;
use OGame\Factories\PlanetServiceFactory;
use OGame\Models\Planet;
use OGame\Services\PlanetService;

/**
 * The single entry point for anything that changes a hero effect on the
 * production (equip, unequip, talents, governor, rebalance of an item).
 * Production rates are stored on each planet and resources accrue at the
 * stored rate, so the order is strict, in one transaction:
 *
 *  1. lock the kingdoms' planets (same order as updateFleetMissions: ids);
 *  2. settle resources at the OLD rate (PlanetService::update());
 *  3. apply the change;
 *  4. recalculate and save the production rate of EVERY planet.
 *
 * Without 2, a new bonus would apply retroactively; without 4, it would be
 * shown but not applied on the other cities.
 */
class HeroEffectsChanged
{
    public function __construct(
        private readonly PlanetServiceFactory $planetServiceFactory,
        private readonly HeroEffectsResolver $resolver,
    ) {
    }

    /**
     * @template T
     * @param int|list<int> $userIds kingdom(s) whose effects change
     * @param Closure(): T $change
     * @return T
     */
    public function apply(int|array $userIds, Closure $change): mixed
    {
        $userIds = array_values(array_unique(array_filter((array) $userIds, fn (int $id) => $id > 0)));

        return DB::transaction(function () use ($userIds, $change): mixed {
            $planetIds = $userIds === [] ? [] : Planet::whereIn('user_id', $userIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            $planets = [];
            foreach ($planetIds as $planetId) {
                $planet = $this->planetServiceFactory->make((int) $planetId, true);
                if ($planet instanceof PlanetService) {
                    $planet->update();
                    $planets[] = $planet;
                }
            }

            $result = $change();

            foreach ($userIds as $userId) {
                $this->resolver->forget($userId);
            }

            foreach ($planets as $planet) {
                $planet->updateResourceProductionStats(false);
                $planet->save();
            }

            return $result;
        });
    }
}
