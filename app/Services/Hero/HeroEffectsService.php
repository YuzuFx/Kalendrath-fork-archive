<?php

namespace OGame\Services\Hero;

use Illuminate\Support\Facades\DB;
use OGame\Enums\HeroRole;
use OGame\Enums\KingdomItemState;
use OGame\Enums\TalentEffectScope;
use OGame\Models\Hero;
use OGame\Models\HeroTalent;
use OGame\Models\User;

/**
 * Aggregates the mechanical effects of heroes (invested talents + worn
 * equipment) and applies the per-axis ceilings of the GDD balance table.
 *
 * Equipment effects are read live from the item definitions (a rebalance of
 * one definition applies to every copy, decision of 2026-11-19). An effect
 * has an optional target ('*' = the whole axis).
 *
 * A hero's bonus stays active while on mission or unavailable (GDD 5.5), so
 * the hero's status is deliberately ignored here.
 */
class HeroEffectsService
{
    public const string ANY_TARGET = '*';

    /**
     * Uncapped effects of one hero: scope => effect_type => target => total.
     *
     * @param Hero $hero
     * @return array<string, array<string, array<string, float>>>
     */
    public function rawForHero(Hero $hero): array
    {
        return $this->rawForHeroes([$hero->id])[$hero->id] ?? [];
    }

    /**
     * Uncapped effects of several heroes, two queries in total.
     *
     * @param list<int> $heroIds
     * @return array<int, array<string, array<string, array<string, float>>>>
     */
    public function rawForHeroes(array $heroIds): array
    {
        if ($heroIds === []) {
            return [];
        }

        $raw = [];

        $talents = HeroTalent::whereIn('hero_id', $heroIds)->with('talentNode')->get();
        foreach ($talents as $talent) {
            $node = $talent->talentNode;
            if ($node !== null && $node->effect_type !== null && $node->effect_scope !== null && $node->effect_value !== null) {
                $raw[$talent->hero_id][$node->effect_scope->value][$node->effect_type][self::ANY_TARGET]
                    = ($raw[$talent->hero_id][$node->effect_scope->value][$node->effect_type][self::ANY_TARGET] ?? 0.0)
                    + $node->effect_value * $talent->rank;
            }
        }

        // Worn items only; a worn-out item (durability 0, future wear) gives nothing.
        $itemEffects = DB::table('kingdom_items')
            ->join('item_definition_effects', 'item_definition_effects.item_definition_id', '=', 'kingdom_items.item_definition_id')
            ->whereIn('kingdom_items.holder_hero_id', $heroIds)
            ->where('kingdom_items.state', KingdomItemState::EQUIPPED->value)
            ->where(fn ($query) => $query->whereNull('kingdom_items.durability')->orWhere('kingdom_items.durability', '>', 0))
            ->get([
                'kingdom_items.holder_hero_id',
                'item_definition_effects.effect_type',
                'item_definition_effects.effect_scope',
                'item_definition_effects.target',
                'item_definition_effects.value',
            ]);

        foreach ($itemEffects as $row) {
            $heroId = (int) $row->holder_hero_id;
            $scope = (string) $row->effect_scope;
            $type = (string) $row->effect_type;
            $target = (string) $row->target;
            $raw[$heroId][$scope][$type][$target] = ($raw[$heroId][$scope][$type][$target] ?? 0.0) + (float) $row->value;
        }

        return $raw;
    }

    /**
     * Overview of one hero's effects, per scope then per effect type (all
     * targets summed), each capped by the axis ceiling. Display only: the
     * production uses forCity(), which caps the final total.
     *
     * @param Hero $hero
     * @return array<string, array<string, float>> scope value => effect_type => total
     */
    public function forHero(Hero $hero): array
    {
        $totals = [];
        foreach ($this->rawForHero($hero) as $scope => $byType) {
            foreach ($byType as $type => $byTarget) {
                $totals[$scope][$type] = $this->capped($type, array_sum($byTarget), 1.0);
            }
        }

        return $totals;
    }

    /**
     * Empire-wide effects of a player (all targets summed): the main hero's
     * empire-scoped effects plus the advisors' empire-scoped effects, the
     * advisors' sum being limited to a fraction of the hero's own ceiling.
     *
     * @param User $user
     * @return array<string, float> effect_type => total
     */
    public function forEmpire(User $user): array
    {
        $total = [];
        foreach ($this->empireComponents($this->kingdom($user->id)) as $type => $byTarget) {
            $total[$type] = $this->capped($type, array_sum($byTarget), 1.0);
        }

        return $total;
    }

    /**
     * Loads what the effects of a kingdom depend on: its heroes (role,
     * governed planet) and their uncapped effects. One call = three queries.
     *
     * @param int $userId
     * @return list<array{role: HeroRole, planet_id: int|null, effects: array<string, array<string, array<string, float>>>}>
     */
    public function kingdom(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $heroes = Hero::where('user_id', $userId)->get(['id', 'role', 'planet_id']);
        $raw = $this->rawForHeroes(array_values(array_map(intval(...), $heroes->pluck('id')->all())));

        $kingdom = [];
        foreach ($heroes as $hero) {
            $kingdom[] = [
                'role' => $hero->role,
                'planet_id' => $hero->planet_id,
                'effects' => $raw[$hero->id] ?? [],
            ];
        }

        return $kingdom;
    }

    /**
     * Uncapped effects that reach one city: empire effects of the main hero,
     * empire effects of the advisors (their sum limited to a ratio of the
     * axis ceiling), and city effects of every hero governing this city.
     *
     * @param list<array{role: HeroRole, planet_id: int|null, effects: array<string, array<string, array<string, float>>>}> $kingdom
     * @param int $planetId
     * @return array<string, array<string, float>> effect_type => target => total
     */
    public function cityComponents(array $kingdom, int $planetId): array
    {
        $total = $this->empireComponents($kingdom);
        $city = TalentEffectScope::CITY->value;

        foreach ($kingdom as $hero) {
            if ($hero['planet_id'] !== $planetId) {
                continue;
            }
            foreach ($hero['effects'][$city] ?? [] as $type => $byTarget) {
                foreach ($byTarget as $target => $value) {
                    $total[$type][$target] = ($total[$type][$target] ?? 0.0) + $value;
                }
            }
        }

        return $total;
    }

    /**
     * Uncapped effects that reach one army: empire effects of the kingdom,
     * plus the city effects of the governor when the army is the stationary
     * garrison of the governed city. Moving armies (attackers, allied
     * reinforcements) never receive city effects.
     *
     * @param list<array{role: HeroRole, planet_id: int|null, effects: array<string, array<string, array<string, float>>>}> $kingdom
     * @return array<string, array<string, float>> effect_type => target => total
     */
    public function armyComponents(array $kingdom, int|null $garrisonPlanetId): array
    {
        return $garrisonPlanetId !== null ? $this->cityComponents($kingdom, $garrisonPlanetId) : $this->empireComponents($kingdom);
    }

    /**
     * Final bonus on one axis and one target: whole-axis effects plus the
     * effects aimed at this target, capped ONCE on the final total.
     *
     * @param array<string, array<string, float>> $components effect_type => target => total
     */
    public function finalValue(array $components, string $effectType, string $target = self::ANY_TARGET): float
    {
        $byTarget = $components[$effectType] ?? [];
        $value = $byTarget[self::ANY_TARGET] ?? 0.0;
        if ($target !== self::ANY_TARGET) {
            $value += $byTarget[$target] ?? 0.0;
        }

        return $this->capped($effectType, $value, 1.0);
    }

    /**
     * Final bonus of one city on one axis and target (uncached helper; the
     * production goes through HeroEffectsResolver, which caches the kingdom).
     */
    public function forCity(int $userId, int $planetId, string $effectType, string $target = self::ANY_TARGET): float
    {
        return $this->finalValue($this->cityComponents($this->kingdom($userId), $planetId), $effectType, $target);
    }

    /**
     * @param list<array{role: HeroRole, planet_id: int|null, effects: array<string, array<string, array<string, float>>>}> $kingdom
     * @return array<string, array<string, float>> effect_type => target => total
     */
    private function empireComponents(array $kingdom): array
    {
        $empire = TalentEffectScope::EMPIRE->value;
        $main = [];
        $advisors = [];

        foreach ($kingdom as $hero) {
            foreach ($hero['effects'][$empire] ?? [] as $type => $byTarget) {
                foreach ($byTarget as $target => $value) {
                    if ($hero['role'] === HeroRole::MAIN) {
                        $main[$type][$target] = ($main[$type][$target] ?? 0.0) + $value;
                    } else {
                        $advisors[$type][$target] = ($advisors[$type][$target] ?? 0.0) + $value;
                    }
                }
            }
        }

        $ratio = (float) config('heroes.advisors.axis_cap_ratio');
        $total = $main;
        foreach ($advisors as $type => $byTarget) {
            foreach ($byTarget as $target => $value) {
                $total[$type][$target] = ($total[$type][$target] ?? 0.0) + $this->capped($type, $value, $ratio);
            }
        }

        return $total;
    }

    /**
     * Applies the axis ceiling (times a ratio) to a value; an axis with no
     * configured ceiling is left uncapped.
     */
    private function capped(string $effectType, float $value, float $ratio): float
    {
        $cap = config('heroes.axis_caps.' . $effectType);

        return is_numeric($cap) ? min($value, (float) $cap * $ratio) : $value;
    }
}
