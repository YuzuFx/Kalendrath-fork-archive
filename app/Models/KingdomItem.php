<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OGame\Enums\EquipmentSlot;
use OGame\Enums\ItemSourceType;
use OGame\Enums\KingdomItemState;

/**
 * One unique item owned by a kingdom (a users row), possibly worn by one of
 * its heroes. Owned by the kingdom, never by the hero: changing hero moves
 * nothing. Its effects are read live from its definition (a rebalance of one
 * definition applies to every copy, decision of 2026-11-19);
 * definition_version only records the version at acquisition, for audit.
 *
 * @property int $id
 * @property string $uid
 * @property int $item_definition_id
 * @property int $definition_version
 * @property int $owner_user_id
 * @property KingdomItemState $state
 * @property int|null $holder_hero_id
 * @property EquipmentSlot|null $equipped_slot
 * @property string|null $reservation_type
 * @property int|null $reservation_id
 * @property int|null $durability
 * @property ItemSourceType $source_type
 * @property string|null $source_ref
 * @property string $origin_server_key
 * @property int $created_operation_id
 * @property Carbon $acquired_at
 * @property-read ItemDefinition $definition
 */
#[Fillable([
    'item_definition_id',
    'definition_version',
    'owner_user_id',
    'state',
    'holder_hero_id',
    'equipped_slot',
    'source_type',
    'source_ref',
    'origin_server_key',
    'created_operation_id',
    'acquired_at',
])]
class KingdomItem extends Model
{
    use HasUlids;

    /**
     * The ULID lives in "uid"; "id" stays auto-incremented.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uid'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => KingdomItemState::class,
            'equipped_slot' => EquipmentSlot::class,
            'source_type' => ItemSourceType::class,
            'acquired_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ItemDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ItemDefinition::class, 'item_definition_id');
    }

    /**
     * @return BelongsTo<Hero, $this>
     */
    public function holder(): BelongsTo
    {
        return $this->belongsTo(Hero::class, 'holder_hero_id');
    }
}
