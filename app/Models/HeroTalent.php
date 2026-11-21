<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A hero's allocation of talent points into a single talent node.
 *
 * @property int $id
 * @property int $hero_id
 * @property int $talent_node_id
 * @property int $rank
 */
#[Fillable([
    'hero_id',
    'talent_node_id',
    'rank',
])]
#[Table(name: 'hero_talents')]
class HeroTalent extends Model
{
    /**
     * @return BelongsTo
     */
    public function hero(): BelongsTo
    {
        return $this->belongsTo(Hero::class);
    }

    /**
     * @return BelongsTo
     */
    public function talentNode(): BelongsTo
    {
        return $this->belongsTo(TalentNode::class);
    }
}
