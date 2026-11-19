<?php

namespace OGame\Services\Hero;

use OGame\Enums\HeroRole;

/**
 * Request-scoped cache of the hero effects of each kingdom, read by the
 * production calculation (once per building per city otherwise) and by the
 * battle engines. Registered as "scoped" in AppServiceProvider: reset between
 * requests and queue jobs, so it is safe under Octane. Any path that changes
 * an effect calls forget() (HeroEffectsChanged does it).
 *
 * Every effect reaches the whole kingdom: no governor per city (decision of
 * 2026-11-19).
 */
class HeroEffectsResolver
{
    /**
     * @var array<int, list<array{role: HeroRole, effects: array<string, array<string, array<string, float>>>}>>
     */
    private array $kingdoms = [];

    public function __construct(private readonly HeroEffectsService $effects)
    {
    }

    /**
     * Final bonus of a kingdom on one axis and target, capped on the total.
     * Zero without a query for a non-player (id <= 0: NPCs, unit tests).
     */
    public function forKingdom(int $userId, string $effectType, string $target = HeroEffectsService::ANY_TARGET): float
    {
        $components = $this->components($userId);

        return $components === [] ? 0.0 : $this->effects->finalValue($components, $effectType, $target);
    }

    /**
     * Uncapped effects reaching one army of a kingdom (effect_type => target
     * => total): the owner's kingdom effects. Heroes accompanying an army
     * (group scope, table fleet_mission_heroes) do not exist in the alpha.
     * Empty without a query for a non-player (id <= 0).
     *
     * @return array<string, array<string, float>>
     */
    public function forArmy(int $ownerId): array
    {
        return $this->components($ownerId);
    }

    public function forget(int $userId): void
    {
        unset($this->kingdoms[$userId]);
    }

    public function flush(): void
    {
        $this->kingdoms = [];
    }

    /**
     * @return array<string, array<string, float>>
     */
    private function components(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $this->kingdoms[$userId] ??= $this->effects->kingdom($userId);
        if ($this->kingdoms[$userId] === []) {
            return [];
        }

        return $this->effects->kingdomComponents($this->kingdoms[$userId]);
    }
}
