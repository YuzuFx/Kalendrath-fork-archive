<?php

namespace OGame\Services\Hero;

use Illuminate\Support\Facades\DB;
use OGame\Enums\HeroRole;
use OGame\Enums\KingdomItemState;
use OGame\Models\Hero;
use OGame\Models\HeroTalent;
use OGame\Models\User;

/**
 * Aggregates the mechanical effects of heroes (invested talents + worn
 * equipment) and applies the optional per-axis ceilings (config heroes.axis_caps, none by default).
 *
 * Equipment effects are read live from the item definitions (a rebalance of
 * one definition applies to every copy, decision of 2026-11-19). An effect
 * has an optional target ('*' = the whole axis).
 *
 * No governor per city (decision of 2026-11-19): the main hero and the
 * advisors each give their bonuses to the whole kingdom.
 *
 * A hero's bonus stays active while on mission or unavailable (GDD 5.5), so
 * the hero's status is deliberately ignored here.
 */
class HeroEffectsService
{
    public const string ANY_TARGET = '*';

    /**
     * Scopes whose effects reach the whole kingdom (TalentEffectScope values).
     */
    public const array KINGDOM_SCOPES = ['empire', 'city'];

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
     * production uses forKingdom(), which caps the final total.
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
     * Kingdom-wide effects of a player (all targets summed): the main hero's
     * kingdom effects plus the advisors' kingdom effects, the advisors' sum
     * being limited to a fraction of the hero's own ceiling.
     *
     * @param User $user
     * @return array<string, float> effect_type => total
     */
    public function forEmpire(User $user): array
    {
        $total = [];
        foreach ($this->kingdomComponents($this->kingdom($user->id)) as $type => $byTarget) {
            $total[$type] = $this->capped($type, array_sum($byTarget), 1.0);
        }

        return $total;
    }

    /**
     * Loads what the effects of a kingdom depend on: its heroes (role) and
     * their uncapped effects. One call = three queries.
     *
     * @param int $userId
     * @return list<array{role: HeroRole, effects: array<string, array<string, array<string, float>>>}>
     */
    public function kingdom(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $heroes = Hero::where('user_id', $userId)->get(['id', 'role']);
        $raw = $this->rawForHeroes(array_values(array_map(intval(...), $heroes->pluck('id')->all())));

        $kingdom = [];
        foreach ($heroes as $hero) {
            $kingdom[] = [
                'role' => $hero->role,
                'effects' => $raw[$hero->id] ?? [],
            ];
        }

        return $kingdom;
    }

    /**
     * Uncapped effects that reach the whole kingdom (every city, every army
     * of the kingdom): kingdom-scoped effects of the main hero, plus those of
     * the advisors, their sum limited to a ratio of the axis ceiling.
     *
     * There is no governor per city any more (decision of 2026-11-19): a
     * legacy "city" scope (talent_nodes, catalog) counts kingdom-wide, like
     * "empire". "hero" (the hero's own missions) and "group" (an army the
     * hero accompanies, not in the alpha) never reach the kingdom.
     *
     * @param list<array{role: HeroRole, effects: array<string, array<string, array<string, float>>>}> $kingdom
     * @return array<string, array<string, float>> effect_type => target => total
     */
    public function kingdomComponents(array $kingdom): array
    {
        $main = [];
        $advisors = [];

        foreach ($kingdom as $hero) {
            foreach (self::KINGDOM_SCOPES as $scope) {
                foreach ($hero['effects'][$scope] ?? [] as $type => $byTarget) {
                    foreach ($byTarget as $target => $value) {
                        if ($hero['role'] === HeroRole::MAIN) {
                            $main[$type][$target] = ($main[$type][$target] ?? 0.0) + $value;
                        } else {
                            $advisors[$type][$target] = ($advisors[$type][$target] ?? 0.0) + $value;
                        }
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
     * Final bonus of a kingdom on one axis and target (uncached helper; the
     * production goes through HeroEffectsResolver, which caches the kingdom).
     */
    public function forKingdom(int $userId, string $effectType, string $target = self::ANY_TARGET): float
    {
        return $this->finalValue($this->kingdomComponents($this->kingdom($userId)), $effectType, $target);
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
