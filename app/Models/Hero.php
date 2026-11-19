<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OGame\Enums\HeroRole;

/**
 * Pilot model for the hero/governor system (GDD section 5.5).
 * Minimal and non-functional: no recruitment, mission, or bonus logic yet.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $planet_id
 * @property string $name
 * @property string|null $avatar
 * @property string $archetype
 * @property HeroRole $role
 * @property int|null $main_marker
 * @property int $level
 * @property int $xp
 * @property int $talent_points_spent
 * @property string $status
 * @property int $strength
 * @property int $intelligence
 * @property int $willpower
 * @property int $dexterity
 * @property int $attack_power
 * @property int $armor
 * @property int $life
 */
#[Fillable([
    'user_id',
    'planet_id',
    'name',
    'avatar',
    'archetype',
    'role',
    'main_marker',
    'level',
    'xp',
    'talent_points_spent',
    'status',
    'strength',
    'intelligence',
    'willpower',
    'dexterity',
    'attack_power',
    'armor',
    'life',
])]
class Hero extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => HeroRole::class,
        ];
    }

    /**
     * The user (player) that recruited this hero.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The planet this hero governs, if assigned.
     *
     * @return BelongsTo<Planet, $this>
     */
    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }

    /**
     * This hero's talent point allocations.
     *
     * @return HasMany<HeroTalent, $this>
     */
    public function talents(): HasMany
    {
        return $this->hasMany(HeroTalent::class);
    }

    /**
     * Legacy demo equipment (hero_equipment), superseded by kingdom_items on
     * 2026-11-19; the table is dropped once the React hero screen ships.
     *
     * @deprecated use equippedItems() and the kingdom inventory
     * @return HasMany<HeroEquipment, $this>
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(HeroEquipment::class);
    }

    /**
     * Kingdom items this hero wears (the kingdom owns them).
     *
     * @return HasMany<KingdomItem, $this>
     */
    public function equippedItems(): HasMany
    {
        return $this->hasMany(KingdomItem::class, 'holder_hero_id');
    }
}
