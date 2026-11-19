<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only line of the item journal: who, what, how many, from where to where.
 *
 * @property int $id
 * @property int $item_operation_id
 * @property int|null $kingdom_item_id
 * @property string|null $item_uid
 * @property int $item_definition_id
 * @property int $quantity
 * @property int|null $from_owner_user_id
 * @property int|null $to_owner_user_id
 * @property string|null $from_state
 * @property string|null $to_state
 * @property int|null $from_hero_id
 * @property int|null $to_hero_id
 * @property string|null $from_slot
 * @property string|null $to_slot
 */
#[Fillable([
    'item_operation_id',
    'kingdom_item_id',
    'item_uid',
    'item_definition_id',
    'quantity',
    'from_owner_user_id',
    'to_owner_user_id',
    'from_state',
    'to_state',
    'from_hero_id',
    'to_hero_id',
    'from_slot',
    'to_slot',
])]
class ItemMovement extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<ItemOperation, $this>
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(ItemOperation::class, 'item_operation_id');
    }
}
