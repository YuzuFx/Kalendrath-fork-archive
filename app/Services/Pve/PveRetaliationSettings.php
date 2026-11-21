<?php

namespace OGame\Services\Pve;

use InvalidArgumentException;

final class PveRetaliationSettings
{
    private const array LIMITS = [
        'preparation_hours' => [8, 12],
        'max_travel_minutes' => [30, 180],
        'max_processing_delay_minutes' => [5, 30],
        'camp_cooldown_hours' => [8, 24],
        'target_attempts_24h' => [1, 1],
        'target_attempts_168h' => [1, 3],
        'min_account_age_hours' => [48, 168],
        'min_city_age_hours' => [12, 72],
        'min_defense_wood_equivalent' => [40000, 100000],
        'max_combat_axis_ratio' => [0.35, 0.70],
        'max_detachment_share' => [0.10, 0.30],
        'min_garrison_reserve_share' => [0.50, 0.75],
        'max_fuel_stock_share' => [0.05, 0.15],
        'max_loot_stock_share' => [0.05, 0.10],
        'protected_production_hours' => [4, 12],
        'max_loot_production_hours_per_hit' => [1, 2],
        'max_loot_production_hours_24h' => [1, 2],
        'max_loot_production_hours_168h' => [2, 4],
        'max_unit_loss_share_per_hit' => [0.05, 0.10],
        'max_unit_loss_share_24h' => [0.05, 0.10],
        'max_unit_loss_share_168h' => [0.10, 0.20],
    ];

    /**
     * @return array<string, int|float|bool>|null Null means the emergency switch is off.
     */
    public function active(): array|null
    {
        if (config('pve.retaliation.enabled') === false) {
            return null;
        }
        if (config('pve.retaliation.enabled') !== true) {
            throw new InvalidArgumentException('pve.retaliation.enabled must be a boolean.');
        }

        $values = ['enabled' => true];
        foreach (self::LIMITS as $key => [$min, $max]) {
            $value = config('pve.retaliation.' . $key);
            $validType = is_int($min) ? is_int($value) : is_float($value);
            if (!$validType || !is_finite($value) || $value < $min || $value > $max) {
                throw new InvalidArgumentException("Invalid pve.retaliation.{$key}; expected a number in [{$min}, {$max}].");
            }
            $values[$key] = $value;
        }
        foreach (['max_loot_production_hours', 'max_unit_loss_share'] as $prefix) {
            [$hit, $day, $week] = array_map(
                fn (string $window) => $values[$prefix . $window] ?? 0,
                ['_per_hit', '_24h', '_168h'],
            );
            if ($hit > $day || $day > $week) {
                throw new InvalidArgumentException("Retaliation limits for {$prefix} must increase with the time window.");
            }
        }

        return $values;
    }
}
