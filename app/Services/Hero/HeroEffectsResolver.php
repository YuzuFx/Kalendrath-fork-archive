<?php

namespace OGame\Services\Hero;

use OGame\Enums\HeroRole;

/**
 * Request-scoped cache of the hero effects of each kingdom, read by the
 * production calculation (once per building per city otherwise). Registered
 * as "scoped" in AppServiceProvider: reset between requests and queue jobs,
 * so it is safe under Octane. Any path that changes an effect calls forget()
 * (HeroEffectsChanged does it).
 */
class HeroEffectsResolver
{
    /**
     * @var array<int, list<array{role: HeroRole, planet_id: int|null, effects: array<string, array<string, array<string, float>>>}>>
     */
    private array $kingdoms = [];

    public function __construct(private readonly HeroEffectsService $effects)
    {
    }

    /**
     * Final bonus of a city on one axis and target, capped on the total.
     * Zero without a query for a non-player (id <= 0: NPCs, unit tests).
     */
    public function forCity(int $userId, int $planetId, string $effectType, string $target = HeroEffectsService::ANY_TARGET): float
    {
        if ($userId <= 0) {
            return 0.0;
        }

        $this->kingdoms[$userId] ??= $this->effects->kingdom($userId);
        if ($this->kingdoms[$userId] === []) {
            return 0.0;
        }

        return $this->effects->finalValue($this->effects->cityComponents($this->kingdoms[$userId], $planetId), $effectType, $target);
    }

    /**
     * Uncapped effects reaching one army (effect_type => target => total):
     * the owner's empire effects, plus the city effects of the governor when
     * the army is the stationary garrison of that city. Heroes accompanying
     * an army (group scope, table fleet_mission_heroes) do not exist in the
     * alpha. Empty without a query for a non-player (id <= 0).
     *
     * @return array<string, array<string, float>>
     */
    public function forArmy(int $ownerId, int|null $garrisonPlanetId): array
    {
        if ($ownerId <= 0) {
            return [];
        }

        $this->kingdoms[$ownerId] ??= $this->effects->kingdom($ownerId);
        if ($this->kingdoms[$ownerId] === []) {
            return [];
        }

        return $this->effects->armyComponents($this->kingdoms[$ownerId], $garrisonPlanetId);
    }

    public function forget(int $userId): void
    {
        unset($this->kingdoms[$userId]);
    }

    public function flush(): void
    {
        $this->kingdoms = [];
    }
}
