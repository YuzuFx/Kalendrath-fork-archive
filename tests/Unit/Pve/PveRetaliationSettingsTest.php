<?php

use OGame\Services\Pve\PveRetaliationSettings;
use Tests\TestCase;

uses(TestCase::class);

it('loads the initial retaliation limits', function (): void {
    config(['pve.retaliation.enabled' => true]);

    expect(resolve(PveRetaliationSettings::class)->active())
        ->toMatchArray(['preparation_hours' => 8, 'max_unit_loss_share_per_hit' => 0.10, 'target_attempts_168h' => 2]);
});

it('disables offensive processing even when other parameters are malformed', function (): void {
    config(['pve.retaliation.enabled' => false, 'pve.retaliation.preparation_hours' => 0]);

    expect(resolve(PveRetaliationSettings::class)->active())->toBeNull();
});

it('rejects unsafe or malformed settings', function (string $key, mixed $value): void {
    config(['pve.retaliation.enabled' => true]);
    config(["pve.retaliation.{$key}" => $value]);

    expect(fn () => resolve(PveRetaliationSettings::class)->active())->toThrow(InvalidArgumentException::class);
})->with([
    ['enabled', 'false'],
    ['preparation_hours', 7],
    ['max_travel_minutes', 181],
    ['max_unit_loss_share_per_hit', 0.11],
    ['max_loot_production_hours_24h', 1],
    ['max_loot_stock_share', NAN],
]);
