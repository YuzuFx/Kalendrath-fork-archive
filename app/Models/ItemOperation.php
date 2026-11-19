<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OGame\Enums\ItemOperationType;

/**
 * Header of the item journal: one inventory change, unique by idempotency key.
 *
 * @property int $id
 * @property string $uid
 * @property string $idempotency_key
 * @property string $payload_hash
 * @property ItemOperationType $type
 * @property int $subject_user_id
 * @property int|null $actor_user_id
 * @property string $server_key
 * @property string|null $source_type
 * @property string|null $source_ref
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 * @property-read Collection<int, ItemMovement> $movements
 */
#[Fillable([
    'idempotency_key',
    'payload_hash',
    'type',
    'subject_user_id',
    'actor_user_id',
    'server_key',
    'source_type',
    'source_ref',
    'meta',
])]
class ItemOperation extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /**
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
            'type' => ItemOperationType::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<ItemMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(ItemMovement::class);
    }
}
