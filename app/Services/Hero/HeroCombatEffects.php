<?php

namespace OGame\Services\Hero;

use OGame\GameObjects\Models\Enums\GameObjectType;
use OGame\GameObjects\Models\UnitObject;
use OGame\Services\PlayerService;

/**
 * Hero effects in battle (Kalendrath, alpha lot 2), resolved ARMY BY ARMY:
 * a hero only strengthens the units of its own kingdom's army. An allied
 * army sent to defend a city brings its own kingdom's effects (or none),
 * never those of the city owner.
 *
 * - troop_combat_bonus_percent: attack, shield and structural integrity of
 *   the troops (ships) of the army. Empire scope (and city scope for the
 *   garrison of the governed city).
 * - garrison_defense_bonus_percent: shield and structural integrity of every
 *   unit (troops and defenses) of the stationary garrison of the city the
 *   hero governs (city scope), only when that city is attacked.
 *
 * Each bonus is additive on the base value of the property (like research
 * and the production bonus): floor(base x % / 100), on top of the upstream
 * value. Each axis is capped ONCE on its final total (config heroes.axis_caps).
 * Without heroes the upstream values are returned unchanged.
 */
class HeroCombatEffects
{
    public const string TROOP_COMBAT = 'troop_combat_bonus_percent';

    public const string GARRISON_DEFENSE = 'garrison_defense_bonus_percent';

    public function __construct(private readonly HeroEffectsResolver $resolver, private readonly HeroEffectsService $effects)
    {
    }

    /**
     * Hero bonus percentages of one unit type in one army.
     *
     * @param int $ownerId Owner of the army (<= 0: NPC, no hero).
     * @param int|null $garrisonPlanetId Planet whose stationary garrison this army is, NULL for any moving army.
     * @return array{attack: float, shield: float, structural_integrity: float}
     */
    public function percents(int $ownerId, int|null $garrisonPlanetId, UnitObject $unit): array
    {
        $none = ['attack' => 0.0, 'shield' => 0.0, 'structural_integrity' => 0.0];
        $components = $this->resolver->forArmy($ownerId, $garrisonPlanetId);
        if ($components === []) {
            return $none;
        }

        $troop = $unit->type === GameObjectType::Ship
            ? $this->effects->finalValue($components, self::TROOP_COMBAT, $unit->machine_name)
            : 0.0;
        $garrison = $garrisonPlanetId !== null
            ? $this->effects->finalValue($components, self::GARRISON_DEFENSE, $unit->machine_name)
            : 0.0;

        return [
            'attack' => $troop,
            'shield' => $troop + $garrison,
            'structural_integrity' => $troop + $garrison,
        ];
    }

    /**
     * Battle values of one unit type in one army: upstream value (research,
     * class) plus the hero bonus of this army's owner.
     *
     * @return array{structural_integrity: int, shield: int, attack: int}
     */
    public function unitStats(UnitObject $unit, PlayerService $player, int $ownerId, int|null $garrisonPlanetId): array
    {
        $percents = $this->percents($ownerId, $garrisonPlanetId, $unit);
        $stats = [];
        foreach (['structural_integrity', 'shield', 'attack'] as $property) {
            $details = $unit->properties->{$property}->calculate($player);
            $stats[$property] = $details->totalValue + (int) floor($details->rawValue * $percents[$property] / 100);
        }

        /** @var array{structural_integrity: int, shield: int, attack: int} $stats */
        return $stats;
    }
}
