<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A PvE bot camp (Kalendrath): marks a planet of the PvE bot account as a
 * camp. See OGame\Services\Pve\PveCampService.
 *
 * @property int $id
 * @property int $planet_id
 * @property string $camp_type
 * @property int $tier
 * @property string $loot_table_code
 * @property Carbon|null $respawn_at
 * @property int $raids_won
 */
#[Fillable(['planet_id', 'camp_type', 'tier', 'loot_table_code', 'respawn_at', 'raids_won'])]
class PveCamp extends Model
{
    protected function casts(): array
    {
        return [
            'respawn_at' => 'datetime',
            'tier' => 'integer',
            'raids_won' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Planet, $this>
     */
    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }
}
